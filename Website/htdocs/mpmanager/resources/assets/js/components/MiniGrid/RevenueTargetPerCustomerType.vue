<template>
<div>
    <widget
        :id="'revenue-targets'"
        :headless="true"
        :title="$tc('phrases.revenueTargetsPerCustomerType')"
        color="green">

        <GChart
            type="ColumnChart"
            :data="chartData"
            :options="chartOptions"
            :resizeDebounce="500"
        ></GChart>

    </widget>
</div>
</template>

<script>
import Widget from '../../shared/widget'
import { BatchRevenueService } from '../../services/BatchRevenueService'

export default {
    name: 'RevenueTargetPerCustomerType',
    components:{ Widget },
    data(){
        return{
            batchRevenueService: new BatchRevenueService(),
            chartData: [],
            tooltip: {isHtml: true},
            chartOptions: {
                height:500,
                legend:'none',
                hAxis: {
                    textPosition: 'out',
                    textStyle: {
                        fontSize: 8
                    }
                },
                tooltip: { isHtml: true },
                title: this.$tc('phrases.revenueTargetsPerCustomerType'),
                vAxis: {
                    viewWindow:{
                        min:0,
                        max:1
                    },
                    format:'#,###%',
                    title: 'Percentage of Targeted Revenue %',

                }
            }
        }
    },
    methods:{
        getColumnChartData(data){
            if(typeof (data) === undefined || data.revenueList === null){
                return
            }
            try {
                this.chartData = this.batchRevenueService.initializeColumnChart(data)
            }catch (e) {
                this.alertNotify('error', e.message)
            }
        },
        alertNotify (type, message) {
            this.$notify({
                group: 'notify',
                type: type,
                title: type + ' !',
                text: message,
                speed: 0
            })
        }
    }
}
</script>

<style scoped>

</style>
