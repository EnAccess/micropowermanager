<template>
    <div>
        <md-dialog :md-active.sync="modalVisibility" :md-click-outside-to-close="false"
        >
            <md-dialog-title>
                {{ $tc('phrases.selectMiniGrid',1) }}
            </md-dialog-title>
            <md-dialog-content>
                <div class="md-layout md-gutter">
                    <div class="md-layout-item md-size-100">
                        <div class="selection">
                            <md-field>
                                <md-select v-if="miniGrids.length>0" v-model="selectedMiniGrid"
                                           placeholder="MiniGrid"
                                           @md-selected="setMiniGrid">
                                    <md-option v-for="(miniGrid ,key)  in miniGrids" :key="key"
                                               :value="miniGrid.id" style="display:inline-flex">
                                        &nbsp;<span>{{miniGrid.name}}</span>
                                        <div v-show="miniGrid.data_stream === 1"
                                             class="selection-active">
                                            <md-icon>check</md-icon>
                                            <md-tooltip md-direction="top"> {{ $tc('phrases.selectMiniGrid',2) }}
                                            </md-tooltip>
                                        </div>

                                    </md-option>

                                </md-select>
                            </md-field>
                        </div>
                    </div>
                </div>


            </md-dialog-content>
        </md-dialog>
    </div>
</template>

<script>

import {MiniGridService} from '../../services/MiniGridService'

export default {
    name: 'Selector',
    created() {
        this.getMiniGridList()
    },
    mounted() {

    },
    data() {
        return {
            miniGridService: new MiniGridService(),
            modalVisibility: false,
            miniGrids: [],
            selectedMiniGrid: null
        }
    },
    methods: {
        async getMiniGridList() {
            try {
                this.miniGrids = await this.miniGridService.getMiniGrids()
                this.showSelector()
            } catch (e) {
                this.alertNotify('error', e.message)
            }

        },

        showSelector() {
            this.modalVisibility = true
        },
        setMiniGrid(miniGridId) {
            this.modalVisibility = true
            this.$router.replace('/dashboards/mini-grid/' + miniGridId)
        },
        alertNotify(type, message) {
            this.$notify({
                group: 'notify',
                type: type,
                title: type + ' !',
                text: message
            })
        },
    }
}
</script>

<style scoped>
    .selection {
        min-width: 300px;
        margin: auto;
        align-items: center;
        text-align: center;

    }

    .selection-active {
        text-align: end;
        color: green;
    }

</style>
