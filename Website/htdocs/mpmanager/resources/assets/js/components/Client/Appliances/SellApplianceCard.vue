<template>
    <div>
        <widget
            color="red"
            title="Sell Appliance "
        >
                <form novalidate class="md-layout"  @submit.prevent="saveAppliance">
                    <md-card class="md-layout-item md-size-100">
                        <md-card-content>
                            <md-field :class="{'md-invalid': errors.has($tc('words.appliance'))}">
                                <label for="appliance">{{ $tc('words.appliance') }}</label>
                                <md-select :name="$tc('words.appliance')" id="appliance" v-model="applianceTypeIndex"
                                >
                                    <md-option disabled value>--{{ $tc('words.select') }}--</md-option>
                                    <md-option
                                        :value="index"
                                        v-for="(appliance, index) in assetService.list"
                                        :key="appliance.id"
                                    >{{ appliance.name }}
                                    </md-option>
                                </md-select>
                                <span class="md-error">{{ errors.first($tc($tc('words.appliance'))) }}</span>
                            </md-field>
                            <md-field :class="{'md-invalid': errors.has($tc('words.cost'))}">
                                <label for="Cost">{{ $tc('words.cost') }}</label>
                                <md-input type="number"
                                          :name="$tc('words.cost')"
                                          id="Cost"
                                          v-model="newAppliance.cost"
                                          @change="checkDownPayment"
                                          v-validate="'required|decimal'"/>
                                <span class="md-error">{{ errors.first($tc('phrases.ratesCount')) }}</span>
                            </md-field>

                            <md-field :class="{'md-invalid': errors.has('Down Payment')}">
                                <label for="Down Payment">Down Payment</label>
                                <md-input type="number"
                                          name="Down Payment"
                                          id="Down Payment"
                                          v-model="newAppliance.downPayment"
                                          v-validate="'required|decimal'"
                                          @change="checkDownPayment"/>
                                <span class="md-error">{{ errors.first('Down Payment') }}</span>
                            </md-field>

                            <md-field :class="{'md-invalid': errors.has($tc('words.rate'))}" v-if="applianceRate">
                                <label for="rate">{{ $tc('phrases.ratesCount') }}</label>
                                <md-input type="number"
                                          :name="$tc('phrases.ratesCount')"
                                          id="rate"
                                          v-model="newAppliance.rate"
                                          v-validate="'required|integer'"
                                />
                                <span class="md-error">{{ errors.first($tc('words.rate')) }}</span>
                            </md-field>
                        </md-card-content>
                        <md-card-actions>
                            <md-button v-if="showRatesButton" class="md-accent md-raised" @click="showRates = true"> Show
                                Rates Detail
                            </md-button>
                            <md-button type="submit" class="md-primary md-raised ">{{ $tc('words.sell') }}</md-button>
                        </md-card-actions>
                    </md-card>

                </form>
        </widget>
        <md-dialog :md-active.sync="showRates">
            <md-dialog-title>
                Cost: {{ moneyFormat(newAppliance.cost) + $store.getters['settings/getMainSettings'].currency }} <br>
                Down Payment : {{ moneyFormat(newAppliance.downPayment) + $store.getters['settings/getMainSettings'].currency }}<br>
                Rates: {{ newAppliance.rate }}

            </md-dialog-title>
            <md-dialog-content>
                <div v-if="newAppliance.rate">
                    <div v-for="x in parseInt(newAppliance.rate)" :key="x">
                        <span v-if="x<10" style="opacity: 0;">0</span>
                        {{ x }}&nbsp;-&nbsp;{{
                            readable(getRate(x,
                                newAppliance.rate, newAppliance.cost - newAppliance.downPayment,))
                        }} {{ $store.getters['settings/getMainSettings'].currency }}
                    </div>
                </div>
            </md-dialog-content>
            <md-dialog-actions>
                <md-button class="md-primary" @click="showRates = false">Close</md-button>
            </md-dialog-actions>
        </md-dialog>
    </div>


</template>

<script>
import widget from '../../../shared/widget'
import { AssetService } from '../../../services/AssetService'
import { AssetPersonService } from '../../../services/AssetPersonService'
import { currency } from '../../../mixins/currency'
export default {
    name: 'SellApplianceCard',
    components: { widget },
    mixins: [currency],
    props: {
        personId: {
            required: true,
        }
    },
    data () {
        return {
            newAppliance: {
            },
            applianceTypeIndex: null,
            adminId: this.$store.getters['auth/getAuthenticateUser'].id,
            applianceRate: true,
            showRates: false,
            assetService: new AssetService(),
            assetPersonService: new AssetPersonService(),
            currency: this.$store.getters['settings/getMainSettings'].currency
        }
    },
    watch:{
        applianceTypeIndex(){
            this.newAppliance.id = this.assetService.list[this.applianceTypeIndex].id
            this.newAppliance.cost = this.newAppliance.preferredPrice = String(this.assetService.list[this.applianceTypeIndex].price)
            this.newAppliance.downPayment = 0
        }
    },
    computed:{
        showRatesButton(){
            if(this.newAppliance.rate > 1 ){
                return true
            }else{
                return false
            }
        }
    },
    mounted () {
        this.getAssetTypesList()
    },
    methods: {
        async getAssetTypesList () {
            try {
                await this.assetService.getAssets()
            } catch (e) {
                this.alertNotify('error', e.message)
            }
        },
        getRate (index, rateCount, cost) {
            if (index === parseInt(rateCount)) {
                return cost - (rateCount - 1) * Math.floor(cost / rateCount)
            } else {
                return Math.floor(cost / rateCount)
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
        async saveAppliance () {
            let validator = await this.$validator.validateAll()
            if (validator) {
                this.$swal({
                    type: 'question',
                    title: this.$tc('phrases.sellAsset', 0),
                    text: this.$tc('phrases.sellAsset', 2, { cost: this.newAppliance.cost + this.$store.getters['settings/getMainSettings'].currency }),
                    showCancelButton: true,
                    cancelButtonText: this.$tc('words.cancel'),
                    confirmButtonText: this.$tc('words.sell')
                }).then(async result => {
                    if (result.value) {
                        try {
                            let validator = await this.$validator.validateAll()
                            if (validator) {
                                let appliance = await this.assetPersonService.saveAsset(this.newAppliance.id, this.personId, this.newAppliance, this.adminId)
                                this.alertNotify('success', this.$tc('phrases.sellAsset', 1))
                                await this.$router.push('/sold-appliance-detail/' + appliance.id)
                            }
                        } catch (e) {
                            this.alertNotify('error', e.message)
                        }
                    }
                })
            }
        },
        checkDownPayment () {
            if (parseFloat(this.newAppliance.downPayment)  > parseFloat(this.newAppliance.cost) ) {
                this.newAppliance.downPayment = 0
                this.alertNotify('warn', 'Down Payment is not bigger than Appliance Cost')
            } else if (this.newAppliance.cost === this.newAppliance.downPayment) {
                this.newAppliance.rate = 0
                this.applianceRate = false
            }else {
                this.applianceRate = true
            }
        }
    }
}
</script>

<style scoped>
</style>
