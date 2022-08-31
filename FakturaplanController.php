<?php


namespace App\Http\Controllers\Orders\Automatisierung;



use App\Domain\Repositories\Interfaces\IFinanceOrderRepository;
use App\Domain\Repositories\Interfaces\Iv_OfferFakturaLbuRepository;
use App\Domain\ValueObjects\SIN;
use App\Exceptions\Business\ValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\UpdateAutomation\StoreFakturaPlanRequest;
use App\Services\Orders\Einstellungen\FakturaplanService;
use App\Utils\SimpleFlash\SimpleFlash;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class FakturaplanController extends Controller
{
    /**
     * @param StoreFakturaPlanRequest $request
     * @param FakturaplanService $service
     * @return JsonResponse
     */
    public function create(StoreFakturaPlanRequest $request, FakturaplanService $service): JsonResponse
    {
        try {
            $dto = $request->getDto();
            $service->create($dto);
        } catch (ValidationException $exception) {
            return new JsonResponse([SimpleFlash::error($exception->getMessage())], 422);
        }
        catch (Exception $exception) {
            Log::error("Couldn't create fakturaplan data: " .$exception->getMessage());
            return new JsonResponse([SimpleFlash::error(__('errors.generic_error'))], 500);
        }
        return new JsonResponse([SimpleFlash::success(__('success.order.einstellungen.fakturaplan.fakturaplan_is_created'))]);
    }

    /**
     * @param int $simpleId
     * @param int $id
     * @param StoreFakturaPlanRequest $request
     * @param FakturaplanService $service
     * @return JsonResponse
     */
    public function update(int $simpleId, int $id, StoreFakturaPlanRequest $request, FakturaplanService $service): JsonResponse
    {
        try {
            $sin = new SIN($simpleId);
            if ($service->checkPermissions($sin)) {
                $dto = $request->getDto();
                $service->update($id, $dto);
                return new JsonResponse([SimpleFlash::success(__('success.order.einstellungen.fakturaplan.fakturaplan_is_updated'))]);
            } else {
                return new JsonResponse([SimpleFlash::error(__('errors.operation_forbidden'))], 403);
            }
        } catch (Exception $e) {
            Log::error("Couldn't update fakturaplan data: " . $e->getMessage());
            return new JsonResponse([SimpleFlash::error(__('errors.generic_error'))], 500);
        }
    }

    /**
     * @param int $id
     * @param FakturaplanService $service
     * @return JsonResponse
     * @throws Exception
     */
    public function delete(int $id, FakturaplanService $service): JsonResponse
    {
        try {
            $service->delete($id);
            return new JsonResponse([SimpleFlash::success(__('success.order.einstellungen.fakturaplan.fakturaplan_is_deleted'))]);
        } catch (Exception $e) {
            Log::error("Couldn't remove fakturaplan {$id}: " . $e->getMessage());
            throw new Exception(__('errors.order.einstellungen.deletion.fakturaplan_wasnt_deleted'));
        }
    }

    /**
     * @param int $simpleId
     * @param Iv_OfferFakturaLbuRepository $repository
     * @param IFinanceOrderRepository $orderRepository
     * @return JsonResponse
     */
    public function getLbuList(int $simpleId, Iv_OfferFakturaLbuRepository $repository, IFinanceOrderRepository $orderRepository): JsonResponse
    {
        $lbuList = $repository->getBasisLbuOptions($simpleId);
        $financeOrderList = $orderRepository->findBySIN($simpleId, $onlyActive = true);
        return new JsonResponse(
            [
                'lbuList' => $lbuList,
                'financeOrderList' => $financeOrderList
            ]
        );
    }
}
