<template>
    <div>
        <PagePreloader v-if="!isStoreInit"/>
        <div v-else>
            <HeaderPanel :simple-id="simpleId"  class="mb-4"/>
            <UploadFile  v-if="simpleId" @upload-files="(val) => attachedfiles = val" class="mb-4" />

            <div class="simple-box box-shadow position-relative">
                <LbuTable :simple-id="simpleId" :attachedfiles="attachedfiles"/>
            </div>
        </div>
    </div>
</template>

<script>
import PagePreloader from "./PagePreloader";
import HeaderPanel from "./HeaderPanel";
import UploadFile from "./UploadFile";
import Loading from "@comp/DynamicImportHelpers/Loading";
import {mapGetters, mapState, mapMutations} from "vuex";

const LbuTable = () => ({loading: Loading, component: import('./LBUTable/List'), delay: 0});

export default {
    name: "send-lbu-list",
    components: {
        PagePreloader, HeaderPanel, UploadFile, LbuTable
    },
    props: {
        simpleId: {
            type: Number,
            required: false,
            default: null
        }
    },
    data() {
        return {
            lbuList: [],
            attachedfiles: [],
            pending: false,
        }
    },
    computed: {
        ...mapGetters({
            isStoreInit: 'sendLbuList/isInit'
        }),
    },
    methods: {
        ...mapMutations({
            setUserPreferences: 'sendLbuList/SET_USER_PREFERENCES'
        })
    }
}
</script>

<style scoped>

</style>
