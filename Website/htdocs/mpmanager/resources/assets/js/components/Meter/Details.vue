<template>
    <widget>
        <div class="meter-overview-card">
            <div class="md-subheading">{{ $tc('phrases.meterDetail', 2) }}</div>
            <div class="meter-overview-detail" v-if="meter!==null && meter.loaded===true">
                <div class="md-layout">
                    <div class="md-layout-item">{{ $tc('words.manufacturer') }}</div>
                    <div
                        class="md-layout-item"
                    >{{ meter.manufacturer.name }} ( {{ meter.manufacturer.website }})
                    </div>
                </div>

                <div class="md-layout">
                    <div class="md-layout-item">{{ $tc('phrases.serialNumber') }}</div>
                    <div class="md-layout-item">{{ meter.serialNumber }}</div>
                </div>
                <div class="md-layout">
                    <div class="md-layout-item">{{ $tc('words.tariff') }}</div>
                    <div class="md-layout-item">
                        <div v-if="editTariff===false">
                            {{ meter.tariff.name }}
                            <span style="cursor: pointer" @click="editTariff = true"><md-icon>edit</md-icon></span>
                        </div>
                        <div class="md-layout" v-else>
                            <div class="md-layout-item">
                                <md-field>
                                    <label for="tariff">{{ $tc('words.tariff') }}</label>
                                    <md-select name="tariff" v-model="newTariff">
                                        <md-option v-for="tariff in tariffService.list"
                                                   :key="tariff.id" :value="tariff.id">
                                            {{ tariff.name }} {{
                                                tariff.price / 100
                                            }}
                                        </md-option>
                                    </md-select>
                                </md-field>
                            </div>
                            <md-button class="md-icon-button" @click="updateTariff(newTariff)">
                                <md-icon class="md-primary">save</md-icon>
                            </md-button>
                            <md-button class="md-icon-button" @click="editTariff=false">
                                <md-icon class="md-accent">cancel</md-icon>
                            </md-button>
                        </div>
                    </div>
                </div>
                <div class="md-layout">
                    <div class="md-layout-item">{{ $tc('phrases.connectionType') }}</div>
                    <div class="md-layout-item">
                        <div v-if="editConnection===false" >
                            {{ meter.connection.name }}
                            <span style="cursor: pointer" @click="editConnection = true"><md-icon>edit</md-icon></span>
                        </div>
                        <div class="md-layout" v-else>
                            <div class="md-layout-item">

                                <md-field>
                                    <label
                                        for="connectiontype">{{ $tc('phrases.connectionType') }}</label>
                                    <md-select name="connectiontype"
                                               v-model="newConnectionType">
                                        <md-option v-for="connectionType in connectionTypes.list"
                                                   :key="connectionType.id" :value="connectionType.id">
                                            {{ connectionType.name }}
                                        </md-option>
                                    </md-select>
                                </md-field>
                            </div>
                            <md-button class="md-icon-button"
                                       @click="updateConnection(newConnectionType)">
                                <md-icon class="md-primary">save</md-icon>
                            </md-button>
                            <md-button class="md-icon-button" @click="editConnection=false">
                                <md-icon class="md-accent">cancel</md-icon>
                            </md-button>
                        </div>
                    </div>
                </div>
                <div class="md-layout">
                    <div class="md-layout-item">{{ $tc('phrases.lastPayment') }}</div>
                    <div class="md-layout-item">{{ $tc('phrases.3daysAgo') }}</div>
                </div>
            </div>
        </div>
    </widget>
</template>

<script>
import Widget from '../../shared/widget'
import { TariffService } from '../../services/TariffService'
import { ConnectionTypes } from '../../classes/connection/ConnectionTypes'
import { MeterParameterService } from '../../services/MeterParameterService'
export default {
    name: 'Details.vue',
    components:{ Widget },
    props:{
        meter:{
            type:Object
        }
    },
    mounted () {
        this.getTariffs()
        this.connectionTypes.getSubConnectionTypes()
    },
    data(){
        return{
            meterParameterService: new MeterParameterService(),
            tariffService: new TariffService(),
            connectionTypes: new ConnectionTypes(),
            editTariff: false,
            newTariff: null,
            newConnectionType: null,
            editConnection: false,
        }
    },
    methods:{
        updateTariff (tariffId) {
            this.updateParameter(this.meter.id, { tariffId: tariffId })
        },
        updateParameter (meterId, params) {
            this.meterParameterService.update(meterId, params)
                .then(response => {
                    if (response.status === 200) {
                        if ('tariff' in response.data.data) {
                            this.meter.tariff = response.data.data.tariff
                        } else if ('connection_type' in response.data.data) {
                            this.meter.connection = response.data.data.connection_type
                        }
                    } else {
                        this.$swal({
                            type: 'error',
                            title: this.$tc('phrases.meterDetailNotify', 0),
                            text: this.$tc('phrases.meterDetailNotify', 2)
                        })
                    }
                    this.editTariff = false
                    this.editConnection = false
                })
        },
        async getTariffs () {
            try {
                await this.tariffService.getTariffs()
            } catch (e) {
                this.alertNotify('error', e.message)
            }
        },
        updateConnection (connectionId) {
            let data = { connectionId: connectionId }
            this.updateParameter(this.meter.id, data)
        },
    }
}
</script>

<style scoped>
</style>
