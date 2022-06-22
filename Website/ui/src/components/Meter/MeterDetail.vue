<template>
    <div class="page-container" id="widget-grid">
        <div class="md-layout">
            <div class="md-layout-item md-size-100 md-small-size-100">
                <div class="md-layout md-gutter md-size-100">
                    <div class="md-layout-item md-layout-size-50 md-small-size-100">
                        <meter-basic
                            :meter="meter"
                        />
                    </div>
                    <div class="md-layout-item md-layout-size-50 md-small-size-100">
                        <meter-details
                            :meter="meter"
                        />
                    </div>
                </div>
            </div>
            <div class="md-layout-item md-size-100 md-small-size-100">
                <meter-transactions
                    :transactions="transactions"
                />
            </div>
        </div>

        <div style="margin-top: 1rem;"></div>

        <meter-readings v-if="showMeterReadings"
                        :meter="meter"
        />


    </div>
</template>

<script>
import { Transactions } from '@/classes/meter/transactions'
import {MeterDetailService}  from '@/services/MeterDetailService'
import MeterBasic from './Basic'
import MeterDetails from './Details'
import MeterTransactions from './Transactions'
import MeterReadings from './Readings'
export default {
    name: 'MeterDetail',
    components: { MeterBasic, MeterDetails, MeterTransactions, MeterReadings },
    created () {
        this.getMeterDetails()
        this.getMeterRevenue()
    },
    mounted () {
        this.transactions = new Transactions(this.$route.params.id)
    },
    computed:{
        showMeterReadings(){
            if(this.meter === null){
                return false
            }else if(this.meter.meterType.online === 1){
                return true
            }else{
                return false
            }
        }
    },
    data () {
        return {
            meterDetailService: new MeterDetailService(this.$route.params.id),
            transactions: null,
            meter: null,
        }
    },
    methods:{
        async getMeterDetails(){
            try {
                this.meter = await this.meterDetailService.detail()
            }catch (e) {
                this.alertNotify('error', e.message)
            }
        },
        async getMeterRevenue(){
            try {
                this.meter.totalRevenue = await this.meterDetailService.revenue()
            }  catch (e) {
                this.alertNotify('error', e.message)
            }
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

<style lang="scss">
.md-menu-content {
    z-index: 11 !important;
}

.asd__inner-wrapper {
    margin-left: 0 !important;
}

.asd__wrapper--datepicker-open {
    right: 20px !important;
}

.mt-15 {
    margin-top: 15px;
}

.list-container {
    max-height: 200px;
    overflow: hidden;
    overflow-y: scroll;
}

.list-item {
    padding: 20px;
    margin: 0.5rem 0;
    cursor: pointer;
    border-bottom: 1px dotted;
}

.list-item-info {
    padding: 5px;
    color: #514e50;
    font-size: 0.8rem;
}

.list-item:hover {
    color: white;
    background-color: rgba(15, 15, 15, 0.8);
}

.md-autocomplete-item {
    z-index: 110;
}

.meter-overview-detail {
    margin-top: 1vh;
}

.meter-overview-card {
    min-height: 195px;
}
</style>
