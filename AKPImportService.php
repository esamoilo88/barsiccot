<?php

namespace App\Services\AKPImport;

use App\Domain\Repositories\Interfaces\ICrmGpRepository;
use App\Domain\Signatures\AKPImport\AkpImportSignature;
use App\Domain\ValueObjects\AKP\FileParams;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Finder\SplFileInfo;

final class AKPImportService
{
    private ICrmGpRepository $CrmGp;
    private EntityManagerInterface $em;
    private AkpFileManageService $fileService;
    protected $htmlOutput;
    protected $displayLog = true;

    /**
     * AKPImportService constructor.
     * @param ICrmGpRepository $CrmGp
     * @param EntityManagerInterface $em
     * @param AkpFileManageService $fileService
     */
    public function __construct(
        ICrmGpRepository $CrmGp,
        EntityManagerInterface $em,
        AkpFileManageService $fileService
    )
    {
        $this->CrmGp = $CrmGp;
        $this->em = $em;
        $this->fileService = $fileService;
    }

    /**
     * @param string $format
     * @throws \Exception
     */
    public function import($format = 'html'): void
    {
        $this->htmlOutput = strtolower($format) == 'html';

        $fileDTO =new FileParams(
            config('dbconfig.storage.BASE_PATH_EXTFILES'),
            config('dbconfig.storage.DB_SERVER_STORAGE')
        );
        $fileGP = $this->getFile($fileDTO->getBaseFileDir(),$fileDTO->getGpFileName());
        $fileKunde = $this->getFile($fileDTO->getBaseFileDir(),$fileDTO->getKundeFileName());

        $this->transferFilesToDBServer($fileGP,$fileKunde);

        try {
            $this->clearTempTables();

            $this->CrmGp->bulkQuery(AkpImportSignature::gpIntermediateTable()->value(), AkpImportSignature::dbServerPathDir()->value().$fileGP->getFilename());
            $this->CrmGp->bulkQuery(AkpImportSignature::kundeIntermediateTable()->value(), AkpImportSignature::dbServerPathDir()->value().$fileKunde->getFilename());

            $this->logAKP("Imported". $fileGP->getFilename()." into " . AkpImportSignature::gpIntermediateTable()->value(), false);

            $this->logAKP("Imported ".$fileKunde->getFilename() ." into " . AkpImportSignature::kundeIntermediateTable()->value(), false);

            $this->removeInconsistentEntries();

            $this->CrmGp->mergeGPTable(AkpImportSignature::gpIntermediateTable()->value());
            $this->CrmGp->mergeKundeTable(AkpImportSignature::kundeIntermediateTable()->value());

            $this->rebuildIndex();

            $this->logAKP('Import finished');

            //delete files after import
            if (!$this->fileService->deleteFiles($fileDTO->getBaseFileDir())) {
                $this->logAKP("Coldn't delete the files from the folder:" . $fileDTO->getBaseFileDir(), 'error');
            };

        } catch (\Exception $e) {
            $this->logAKP("Files are NOT imported because of Error: " . $e->getMessage(), 'error');

            if (!$this->fileService->copyErrorFiles($fileDTO->getBaseFileDir(), $fileDTO->getDestinationFolder())) {
                $this->logAKP("Coldn't copy the files into the destination folder:" . $fileDTO->getDestinationFolder(), 'error');
            }
            //delete files after import
            if (!$this->fileService->deleteFiles($fileDTO->getBaseFileDir())) {
                $this->logAKP("Coldn't delete the files from the folder:" . $fileDTO->getBaseFileDir(), 'error');
            };
        }
    }

    /**
     * @param SplFileInfo $fileGp
     * @param SplFileInfo $fileKunde
     * @throws \Exception
     */
    private function transferFilesToDBServer(SplFileInfo $fileGp,SplFileInfo $fileKunde){

        Storage::disk('db_storage')->deleteDirectory('AKP_Daten/');

        if (!Storage::disk('db_storage')->put('AKP_Daten/'. $fileGp->getFilename(), $fileGp->getContents()) ||
            !Storage::disk('db_storage')->put('AKP_Daten/'. $fileKunde->getFilename(), $fileKunde->getContents())) {
            throw new \Exception("Couldn't transfer txt files from the app server to the db server");
        }
  }

    /**
     * @param string $message
     * @param string $type
     */
    private function logAKP(string $message, $type = 'info'): void
    {
        if ($this->displayLog) {
            $this->print($message, $type);
        }
        switch ($type) {
            case 'info':
                Log::channel('akp_import')->info($message);
                break;
            case 'error':
                Log::channel('akp_import')->error($message);
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function clearTempTables(): void
    {
        $tables = [
            AkpImportSignature::gpIntermediateTable()->value(),
            AkpImportSignature::kundeIntermediateTable()->value()
        ];
        foreach ($tables as $table) {
            $this->CrmGp->deleteAKP($table);
            $this->logAKP("Clear intermediate table $table");
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function removeInconsistentEntries(): void
    {
        $this->CrmGp->removeInconsistentEntries(
            AkpImportSignature::kundeIntermediateTable()->value(),
            AkpImportSignature::gpIntermediateTable()->value()
        );
        $this->logAKP("Removed inconsistent GP_NR", false);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function rebuildIndex(): void
    {
        $tables = ['CRM_GP', 'CRM_Kunde'];

        foreach ($tables as $table) {
            $this->CrmGp->alterIndex($table);
            $this->logAKP("Rebuild FULLTEXT index on $table");
        }
    }

    /**
     * @param string $data
     * @param string $type
     */
    protected function print(string $data,string $type):void
    {
        if ($this->htmlOutput) {
            $status = ($type == 'error') ? 'red' : 'green';
            print('<li style="color: ' . $status . '"><span style="color: black;">' . $data . '</span></li>');
        } else {
            $status = ($type == 'error') ? '[ERROR]' : '';
            print(implode(' ', [$status, $data]) . "\r\n\r\n<br>");
        }
    }

    /**
     * @param string $baseFileDir
     * @param string $fileName
     * @return SplFileInfo|null
     */
    private function getFile(string $baseFileDir, string $fileName): ?SplFileInfo
    {
        /** @var SplFileInfo $file */
        $file = new SplFileInfo($baseFileDir.'/'.$fileName,'','');

        if (!$file) {
            return null;
        }
        $fileTime = Carbon::createFromTimestamp($file->getCTime())->format("d.m.Y H:i");
        $fileSize = $file->getSize() / 1024 / 1024;

        $this->logAKP(sprintf('choosing latest file %s, file size: %s, time: %s', $file->getBasename(), $fileSize, $fileTime));
        return $file;
    }

}
