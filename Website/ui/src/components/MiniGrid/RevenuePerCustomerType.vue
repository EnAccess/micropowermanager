<template>
<div>
    <widget
        :id="'revenue-pie'"
        :headless="true"
        :title="$tc('phrases.revenuePerCustomerType')"
        color="red">

        <GChart
                type="PieChart"
                :options="donutChartOptions"
                :data="donutData">

        </GChart>
    </widget>
</div>
</template>

<script>
import Widget from '../../shared/widget'
import {BatchRevenueService} from '@/services/BatchRevenueService'

export default {
    name: 'RevenuePerCustomerType',
    components: { Widget },
    props:{
        batchRevenues: {
            required: true
        }
    },
    data(){
        return{
            batchRevenueService: new BatchRevenueService(),
            donutData:[],
            donutChartOptions: {
                pieHole: 1,
                legend: 'bottom',
                height: 500,
            },
        }
    },
    methods:{
        // get all periods from donut data and maps them into one array
        initDonutData (batchRevenues) {
            this.donutData = this.batchRevenueService.initializeDonutCharts([this.$tc('words.connection'), this.$tc('words.revenue')], batchRevenues)
            this.checkDonutChartOptions(this.donutData)
        },
        checkDonutChartOptions(donutData){
            let value = donutData.reduce((acc, curr) => {
                if (curr[1] > 0) {
                    acc = true
                }
                return acc
            }, false)
            if (value) {
                this.donutChartOptions = {
                    pieHole: 1,
                    legend: 'bottom',
                    height: 500,
                }
            } else {
                this.donutData = []
                this.donutData.push([this.$tc('words.connection'), this.$tc('words.revenue')])
                this.donutData.push(['', { v: 1, f: this.$tc('phrases.noData') }])
                this.donutChartOptions.chartArea = {
                    left: '15%'
                }
                this.donutChartOptions.colors = ['transparent']
                this.donutChartOptions.pieSliceBorderColor = '#9e9e9e'
                this.donutChartOptions.pieSliceText = 'value'
                this.donutChartOptions.pieSliceTextStyle = {
                    color: '#9e9e9e'
                }
                this.donutChartOptions.tooltip = {
                    trigger: 'none'
                }

            }
        }


    }
}
</script>

<style scoped>

</style>
