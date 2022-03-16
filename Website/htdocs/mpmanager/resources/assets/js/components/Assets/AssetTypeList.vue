<template>
    <div>
        <add-asset-type :addNewAssetType="addNewAssetType"/>
        <widget
            :title="$tc('phrases.assetType')"
            :subscriber="subscriber"
            :route_name="'/assets/types'"
            :button="true"
            :button-text="$tc('phrases.newAssetType')"
            @widgetAction="showAddAssetType"
            :paginator="assetService.paginator"
            color="green"
            :reset-key="resetKey">
            <md-table>
                <md-table-row>
                    <md-table-head v-for="(item, index) in headers" :key="index">{{ item }}</md-table-head>
                </md-table-row>

                <md-table-row v-for="(asset,index) in assetService.list" :key="index">

                    <md-table-cell> {{ asset.id }}
                    </md-table-cell>

                    <md-table-cell>
                        <div class="md-layout" v-if="updateAppliance === index">
                            <md-field :class="{'md-invalid': errors.has('Appliance Name')}">
                                <label for="applianceName"></label>
                                <md-input
                                    name="Appliance Name"
                                    type="text"
                                    v-model="asset.name"
                                    v-validate="'required|min:5'"
                                ></md-input>
                                <span class="md-error">{{ errors.first('Appliance Name') }}</span>
                            </md-field>
                        </div>
                        <div class="md-layout-item" v-else>
                            {{ asset.name }}&nbsp;
                        </div>
                    </md-table-cell>

                    <md-table-cell>
                        <div class="md-layout">
                            <div class="md-layout-item" v-if="updateAppliance === index">
                                <md-field :class="{'md-invalid': errors.has('Appliance Price')}">
                                    <label for="price">Price</label>
                                    <span class="md-prefix">{{ currency }}</span>
                                    <md-input
                                        name="Appliance Price"
                                        type="number"
                                        v-model="asset.price"
                                        v-validate="'required|numeric|min_value:1'"
                                    ></md-input>
                                    <span class="md-error">{{ errors.first('Appliance Price') }}</span>
                                </md-field>

                            </div>
                            <div class="md-layout-item" v-else>
                                {{ asset.price }} {{ currency }}
                            </div>
                        </div>
                    </md-table-cell>

                    <md-table-cell>{{ asset.updated_at }}</md-table-cell>
                    <md-table-cell>
                        <div class="md-layout md-gutter" style="cursor: pointer;" v-if="updateAppliance === index">
                            <md-button class="md-primary md-dense"
                                 @click="updateAssetType(asset)">
                                <md-icon class="md-primary">save</md-icon>
                                <span class="md-primary">{{ $tc('words.save') }}</span>
                            </md-button>
                            <md-button class="md-accent md-dense"
                                 @click="closeApplianceUpdate">
                                <md-icon class="md-accent">close</md-icon>
                                <span class="md-accent">{{ $tc('words.close') }}</span>
                            </md-button>
                        </div>
                        <div class="md-layout md-gutter" style="cursor: pointer;" v-else>
                            <md-button class="md-primary md-dense" @click="openApplianceUpdate(index)">
                                <md-icon>edit</md-icon>
                                {{ $tc('words.edit') }}
                            </md-button>
                            <md-button class="md-primary md-accent" :disabled="loading"
                                 @click="deleteAssetType(asset)">
                                <md-icon class="md-accent">delete</md-icon>
                                {{ $tc('words.delete') }}
                            </md-button>
                        </div>
                        <md-progress-bar md-mode="indeterminate" v-if="loading"/>
                    </md-table-cell>

                </md-table-row>

            </md-table>
        </widget>
    </div>

</template>

<script>
import Widget from '../../shared/widget'
import AddAssetType from './AddAssetType'
import { EventBus } from '../../shared/eventbus'
import { AssetService } from '../../services/AssetService'

export default {
    name: 'AssetTypeList',
    components: { Widget, AddAssetType },

    data () {
        return {
            addNewAssetType: false,
            subscriber: 'asset-list',
            assetService: new AssetService(),
            assetTypes: [],
            headers: [this.$tc('words.id'), this.$tc('words.name'), this.$tc('words.price'), this.$tc('phrases.lastUpdate'), ''],
            resetKey: 0,
            loading: false,
            updateAppliance: null,
            currency: this.$store.getters['settings/getMainSettings'].currency
        }
    },
    mounted () {
        EventBus.$on('assetTypeAdded', () => {
            this.resetKey++
        })
        EventBus.$on('pageLoaded', this.reloadList)
        EventBus.$on('addAssetTypeClosed', this.closeAddComponent)

    },
    beforeDestroy () {
        EventBus.$off('assetTypeAdded', this.addToList)
        EventBus.$off('pageLoaded', this.reloadList)

    },
    methods: {
        showAddAssetType () {
            this.addNewAssetType = true
        },
        reloadList (subscriber, data) {
            if (subscriber !== this.subscriber) {
                return
            }
            this.assetService.updateList(data)
            EventBus.$emit('widgetContentLoaded', this.subscriber, this.assetService.list.length)

        },
        addToList (asset_type) {
            let asset_t = {
                id: asset_type.id,
                name: asset_type.name,
                edit: false,
                asset_type_name: asset_type.name
            }
            this.assetService.list.push(asset_t)
        },

        async updateAssetType (asset) {
            let validator = await this.$validator.validateAll()
            if (!validator) {
                return
            }
            this.loading = true
            this.$swal({
                type: 'question',
                title: 'Update Appliance Type',
                text: 'Are you sure to update the asset type ?',
                showCancelButton: true,
                cancelButtonText: this.$tc('words.cancel'),
                confirmButtonText: this.$tc('words.update')
            }).then(async response => {
                if(response.value){
                    this.updateAppliance = false
                    try {
                        await this.assetService.updateAsset(asset)
                        this.alertNotify('success', 'Appliance Type Updated Successfully.')
                        this.resetKey++
                    } catch (e) {
                        this.alertNotify('error', e.message)
                    }
                }
            })
            this.loading = false

        },

        async deleteAssetType (asset_type) {
            this.$swal({
                type: 'question',
                title: this.$tc('phrases.deleteAssetType',0),
                text: this.$tc('phrases.deleteAssetType',2),
                showCancelButton: true,
                cancelButtonText: this.$tc('words.cancel'),
                confirmButtonText: this.$tc('words.delete')
            }).then(async response => {
                if(response.value){
                    try {
                        this.loading = true
                        await this.assetService.deleteAsset(asset_type)
                        this.loading = false
                        this.alertNotify('success', this.$tc('phrases.deleteAssetType',1))
                        this.resetKey++
                    } catch (e) {
                        this.loading = false
                        this.alertNotify('error', e.message)
                    }
                }

            })

        },
        openApplianceUpdate (index) {
            if (this.updateAppliance === index) {
                this.updateAppliance = null
            } else {
                this.updateAppliance = index
            }
        },
        closeApplianceUpdate(){
            this.updateAppliance = null
        },
        closeAddComponent (data) {
            this.addNewAssetType = data
        },
        alertNotify (type, message) {
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

</style>
