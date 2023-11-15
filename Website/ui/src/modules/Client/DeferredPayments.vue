<template>
    <div>

        <widget
            :class="'col-sm-6 col-md-5'"
            :button-text="$tc('phrases.assignAppliance',0)"
            :button="true"
            :title="$tc('phrases.soldAppliances')"
            :button-color="'red'"
            color="green"
            :subscriber="subscriber"
            @widgetAction="soldNewAsset"
        >

            <div>
                <md-table>
                    <md-table-row>
                        <md-table-head>{{ $tc('words.name') }}</md-table-head>
                        <md-table-head>{{ $tc('words.cost') }}</md-table-head>
                        <md-table-head>Down Payment</md-table-head>
                        <md-table-head>{{ $tc('words.rate', 1) }}</md-table-head>
                    </md-table-row>
                    <md-table-row v-for="(item, index) in assetPersonService.list" :key="index">
                        <md-table-cell md-label="Name" md-sort-by="name">{{ item.asset.name }}</md-table-cell>
                        <md-table-cell md-label="Cost" md-sort-by="total_cost">{{ moneyFormat(item.total_cost) }}
                        </md-table-cell>
                        <md-table-cell md-label="Down Payment" md-sort-by="down_payment">
                            {{ moneyFormat(item.down_payment) }}
                        </md-table-cell>
                        <md-table-cell md-label="Rates" md-sort-by="rate_count">
                            {{ item.rate_count }}
                            <div
                                :class="index=== -999?'text-danger':'text-success'"
                                style="cursor: pointer; display:inline-block"
                                @click="showDetails(index)"
                            >
                                <md-icon>remove_red_eye</md-icon>
                                {{ $tc('words.detail', 1) }}
                            </div>
                        </md-table-cell>
                    </md-table-row>
                </md-table>
            </div>

        </widget>
    </div>
</template>

<script>
import { AssetRateService } from '@/services/AssetRateService'
import { AssetPersonService } from '@/services/AssetPersonService'
import { currency } from '@/mixins/currency'
import { EventBus } from '@/shared/eventbus'
import Widget from '../../shared/widget'

export default {
    name: 'DeferredPayments',
    mixins: [currency],
    components: { Widget },
    props: {
        personId: Number
    },
    mounted () {
        this.getAssetList()
    },
    data () {
        return {
            subscriber: 'person-asset',
            assetRateService: new AssetRateService(),
            assetPersonService: new AssetPersonService(),
            adminId: this.$store.getters['auth/authenticationService'].authenticateUser.id,
            selectedAsset: null,
            headers: [this.$tc('words.name'), this.$tc('words.cost'), this.$tc('words.rate', 1)],

        }
    },

    methods: {
        soldNewAsset () {
            this.$router.push('/sell-appliance/' + this.personId)
        },

        showDetails (index) {
            this.selectedAsset = this.assetPersonService.list[index]
            this.$router.push('/sold-appliance-detail/' + this.selectedAsset.id)
        },
        async getAssetList () {
            try {
                await this.assetPersonService.getPersonAssets(this.personId)
                EventBus.$emit('widgetContentLoaded', this.subscriber, this.assetPersonService.list.length)
            } catch (e) {
                this.alertNotify('error', e.message)
            }
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

}
</script>

<style scoped>
.dialog-place {
    max-width: 100%;
    margin: auto !important;
    overflow-y: scroll;
}

.mb {
    margin-bottom: 15px;
    border-bottom: 1px solid #eceaea;
}

.edit {
    color: #2c4074;
    /*position: absolute;*/
    left: 88%;
    cursor: pointer;
}

.edit:hover {
    color: #5562c5;
    transform: scale(1.5);
}

.detail {
    color: #0d746c;
    /*position: absolute;*/
    left: 92%;
    cursor: pointer;
}

.detail:hover {
    color: #1e7f99;
    transform: scale(1.5);
}

.save {
    color: #1d8922;
    /*position: absolute;*/
    left: 92%;
    cursor: pointer;
}

.save:hover {
    color: #8fac25;
    transform: scale(1.5);
}

.cancel {
    color: #74150b;
    /*position: absolute;*/
    left: 92%;
    cursor: pointer;
}

.cancel:hover {
    color: #a81e10;
    transform: scale(1.5);
}

.details-modal-grid {
    padding: 1rem;
}
</style>
