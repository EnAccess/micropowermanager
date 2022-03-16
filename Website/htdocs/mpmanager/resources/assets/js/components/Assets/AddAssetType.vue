<template>
    <div>
        <widget
                :hidden="!addNewAssetType"
                :title="$tc('phrases.newAssetType')"
                color="red"
        >
            <md-card>
                <div class="md-layout md-gutter">
                    <div class="md-layout-item md-large-size-100 md-medium-size-100 md-small-size-100">
                        <md-card-content>
                            <form class="md-layout md-gutter" ref="assetForm">
                                <div class="md-layout-item md-size-50 md-small-size-100 ">
                                    <md-field :class="{'md-invalid': errors.has($tc('words.name'))}">
                                        <label>{{ $tc('words.name') }}</label>
                                        <md-input v-model="assetService.asset.name"
                                                  :placeholder="$tc('words.name')"
                                                  type="text"
                                                  :name="$tc('words.name')"
                                                  id="asset"
                                                  v-validate="'required|min:4'"
                                        ></md-input>
                                        <span class="md-error">{{ errors.first($tc('words.name') )}}</span>
                                    </md-field>
                                </div>
                                <div class="md-layout-item md-size-50 md-small-size-100 ">
                                    <md-field :class="{'md-invalid': errors.has($tc('words.price'))}">
                                        <label>{{ $tc('words.price') }}</label>
                                        <md-input v-model="assetService.asset.price"
                                                  :placeholder="$tc('words.price')"
                                                  type="text"
                                                  :name="$tc('words.price')"
                                                  id="asset_price"
                                                  v-validate="'required|numeric'"
                                        ></md-input>
                                        <span class="md-error">{{ errors.first($tc('words.price')) }}</span>
                                    </md-field>
                                </div>
                            </form>
                            <md-progress-bar md-mode="indeterminate" v-if="loading"/>
                        </md-card-content>
                    </div>
                </div>
                <md-card-actions>
                    <md-button class="md-raised md-primary" @click="saveAsset()" :disabled="loading">
                        {{ $tc('words.save') }}
                    </md-button>
                    <md-button class="md-raised " @click="closeAddComponent()">
                        {{ $tc('words.close') }}
                    </md-button>
                </md-card-actions>
            </md-card>
        </widget>

    </div>

</template>
<script>
import Widget from '../../shared/widget'
import { AssetService } from '../../services/AssetService'
import { EventBus } from '../../shared/eventbus'

export default {
    name: 'AddAssetType',
    components: { Widget },
    props: {
        addNewAssetType: {
            type: Boolean,
            default: false
        }
    },
    data () {
        return {
            assetService: new AssetService(),
            loading: false,
            isMounted: false,
        }
    },
    created () {
        this.asset = this.assetService.asset
    },
    mounted () {
        this.isMounted = true
    },
    methods: {
        async saveAsset () {
            let validation = await this.$validator.validateAll()
            if (!validation) {
                return
            }

            try {
                this.loading = true
                await this.assetService.createAsset()

                this.loading = false
                this.alertNotify('success', this.$tc('phrases.newAssetType', 2))

                EventBus.$emit('AssetTypeAdded',)
            } catch (e) {
                this.loading = false
                this.alertNotify('error', e.message)
            }
            this.closeAddComponent()
        },

        closeAddComponent () {
            EventBus.$emit('addAssetTypeClosed', false)
        },
        alertNotify (type, message) {
            this.$notify({
                group: 'notify',
                type: type,
                title: type + ' !',
                text: message
            })
        },
    },
    watch: {
        addNewAssetType (value) {
            if (value) {
                this.errors.clear()
            }

        }
    }

}
</script>
