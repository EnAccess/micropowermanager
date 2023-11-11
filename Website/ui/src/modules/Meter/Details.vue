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
                            <span style="cursor: pointer" @click="editTariff = true" v-if="meter.tariff.factor!==2"><md-icon>edit</md-icon></span>
                        </div>
                        <div class="md-layout" v-else>
                            <div class="md-layout-item">
                                <md-field>
                                    <label for="tariff">{{ $tc('words.tariff') }}</label>
                                    <md-select name="tariff" v-model="newTariffId">
                                        <md-option v-for="tariff in tariffService.list"
                                                   :key="tariff.id" :value="tariff.id">
                                            {{ tariff.name }} <small> ({{ moneyFormat(tariff.price) }}) </small>
                                        </md-option>
                                    </md-select>
                                </md-field>
                            </div>
                            <md-button class="md-icon-button" @click="updateTariff()">
                                <md-icon class="md-primary">save</md-icon>
                            </md-button>
                            <md-button class="md-icon-button" @click="editTariff=false">
                                <md-icon class="md-accent">cancel</md-icon>
                            </md-button>
                        </div>
                    </div>
                </div>
                <div class="md-layout">
                    <div class="md-layout-item">{{ $tc('phrases.connectionGroup') }}</div>
                    <div class="md-layout-item">
                        <div v-if="!editConnectionGroup">
                            {{ meter.connectionGroup.name }}
                            <span style="cursor: pointer" @click="editConnectionGroup = true"><md-icon>edit</md-icon></span>
                        </div>
                        <div class="md-layout" v-else>
                            <div class="md-layout-item">
                                <md-field>
                                    <label for="connectionGroup">{{ $tc('phrases.connectionGroup') }}</label>
                                    <md-select name="connectionGroup"
                                               v-model="newConnectionGroupId">
                                        <md-option v-for="connectionGroup in connectionGroupService.list"
                                                   :key="connectionGroup.id" :value="connectionGroup.id">
                                            {{ connectionGroup.name }}
                                        </md-option>
                                    </md-select>
                                </md-field>
                            </div>
                            <md-button class="md-icon-button"
                                       @click="updateConnectionGroup()">
                                <md-icon class="md-primary">save</md-icon>
                            </md-button>
                            <md-button class="md-icon-button" @click="editConnectionGroup=false">
                                <md-icon class="md-accent">cancel</md-icon>
                            </md-button>
                        </div>
                    </div>
                </div>
                <div class="md-layout">
                    <div class="md-layout-item">{{ $tc('phrases.connectionType') }}</div>
                    <div class="md-layout-item">
                        <div v-if="editConnectionType===false">
                            {{ meter.connectionType.name }}
                            <span style="cursor: pointer" @click="editConnectionType = true"><md-icon>edit</md-icon></span>
                        </div>
                        <div class="md-layout" v-else>
                            <div class="md-layout-item">

                                <md-field>
                                    <label
                                        for="connectionType">{{ $tc('phrases.connectionType') }}</label>
                                    <md-select name="connectionType"
                                               v-model="newConnectionTypeId">
                                        <md-option v-for="connectionType in connectionTypeService.list"
                                                   :key="connectionType.id" :value="connectionType.id">
                                            {{ connectionType.name }}
                                        </md-option>
                                    </md-select>
                                </md-field>
                            </div>
                            <md-button class="md-icon-button"
                                       @click="updateConnectionType()">
                                <md-icon class="md-primary">save</md-icon>
                            </md-button>
                            <md-button class="md-icon-button" @click="editConnectionType=false">
                                <md-icon class="md-accent">cancel</md-icon>
                            </md-button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </widget>
</template>

<script>
import Widget from '@/shared/widget'
import { TariffService } from '@/services/TariffService'
import { ConnectionTypeService } from '@/services/ConnectionTypeService'
import { ConnectionGroupService } from '@/services/ConnectionGroupService'
import { SubConnectionTypeService } from '@/services/SubConnectionTypeService'
import { MeterService } from '@/services/MeterService'
import { currency } from '@/mixins/currency'

export default {
    name: 'Details.vue',
    mixins: [currency],
    components: { Widget },
    props: {
        meter: {
            type: Object
        }
    },
    mounted () {
        this.getTariffs()
        this.getConnectionGroups()
        this.getConnectionTypes()
    },
    data () {
        return {
            editTariff: false,
            newTariffId: null,
            meterService: new MeterService(),
            tariffService: new TariffService(),
            connectionTypeService: new ConnectionTypeService(),
            connectionGroupService: new ConnectionGroupService(),
            newConnectionGroupId: null,
            newConnectionTypeId: null,
            editConnectionGroup: false,
            editConnectionType: false,
            editSubConnectionType: false,
        }
    },
    methods: {
        async getTariffs () {
            try {
                await this.tariffService.getTariffs()
            } catch (e) {
                this.alertNotify('error', e.message)
            }
        },
        async getConnectionGroups () {
            try {
                await this.connectionGroupService.getConnectionGroups()
            } catch (e) {
                this.alertNotify('error', e.message)
            }
        },
        async getConnectionTypes () {
            try {
                await this.connectionTypeService.getConnectionTypes()
            } catch (e) {
                this.alertNotify('error', e.message)
            }
        },
        async updateTariff () {
            this.$emit('updated', { id: this.meter.id, tariffId: this.newTariffId })
            this.editTariff = false
        },
        async updateConnectionGroup () {
            this.$emit('updated', {id: this.meter.id, connectionGroupId: this.newConnectionGroupId })
            this.editConnectionGroup = false
        },
        async updateConnectionType () {
            this.$emit('updated', {id: this.meter.id, connectionTypeId: this.newConnectionTypeId })
            this.editConnectionType = false
        },
    }
}
</script>

