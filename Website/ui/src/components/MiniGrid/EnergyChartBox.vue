<template>
    <div class="md-layout">
        <md-toolbar class="md-dense">
            <div class="md-toolbar-section-start">
                <h3 class="md-title" > {{$tc('phrases.batteryPvCharts')}} </h3>
                <!--<div class="md-subheader" ><span><b> Resolution:</b> 3 Days</span>&nbsp; | &nbsp;<span><b> Period:</b> {{  }} - {{ todayDate }}</span>  </div>-->
            </div>
            <!--<div class="md-toolbar-section-end">
                <md-button class="md-icon-button">
                    <md-icon>keyboard_arrow_left</md-icon>
                    <md-tooltip md-direction="top">Previous 3 Days</md-tooltip>
                </md-button>
                <md-button class="md-icon-button">
                    <md-icon>keyboard_arrow_right</md-icon>
                    <md-tooltip md-direction="top">Next 3 Days</md-tooltip>
                </md-button>
                <md-button class="md-icon-button">
                    <md-tooltip md-direction="top">Increase Period</md-tooltip>
                    <md-icon>add</md-icon>
                </md-button>
                <md-button class="md-icon-button">
                    <md-tooltip md-direction="top">Decrease Period</md-tooltip>
                    <md-icon>remove</md-icon>
                </md-button>

            </div><!-->
        </md-toolbar>
        <div class="md-layout-item md-size-100">
            <custom-chart
                :data="generationAssetsService.chartData"
                :chart-type="'line'"
                :title="$tc('words.energy')"
                :subscriber="subscriber.energy"
            ></custom-chart>
        </div>
        <div class="md-layout-item md-size-100">
            <custom-chart
                :data="batteryService.stateChartData"
                :chart-type="'line'"
                :title="$tc('phrases.batteryChargeStatus')"
                :subscriber="subscriber.battery"
            ></custom-chart>

        </div>
        <!-- <div class="md-layout-item md-size-100">
            <custom-chart
                :chart-type="'bar'"
                :title="'Energy Status'"
                :subscriber="subscriber.energyStatus"
            ></custom-chart>

        </div>-->


    </div>


</template>

<script>
import { BatteryService } from '@/services/BatteryService'
import CustomChart from '../../shared/CustomChart'
import { GenerationAssetsService } from '@/services/GenerationAssetsService'

export default {
    name: 'EnergyChartBox',
    components: { CustomChart },
    props: {
        miniGridId: {
            required: true
        }
    },
    created () {
        this.initBatteryChart()
        this.initPVChart()

    },
    mounted () {
        //TODO: remove dummy data
        //EventBus.$emit('chartLoaded', this.subscriber.energyStatus)
        this.batteryService.subscriber = this.subscriber.battery
        this.generationAssetsService.setSubscriber(this.subscriber.energy)
    },
    data: () => (
        {
            todayDate: new Date().toJSON().slice(0, 10).replace(/-/g, '/'),
            batteryService: new BatteryService(),
            generationAssetsService: new GenerationAssetsService(),
            subscriber: {
                energy: 'energy',
                //energyStatus: 'energyStatus',
                battery: 'batteryCharge',

            },
        }
    ),
    methods: {
        initGenerationChart () {

        },
        initBatteryChart () {
            this.batteryService.getBatteryUsageList(this.miniGridId, true).
                then((result) => {
                    if (!result) {

                        return
                    }
                    // this.batteryService.prepareChartData()
                })
        },
        initPVChart () {
            this.generationAssetsService.getList(this.miniGridId).then((result) => {
                if (!result) {

                    return
                }
                this.generationAssetsService.prepareChartData()
            })

        }
    }
}
</script>

<style scoped>

</style>
