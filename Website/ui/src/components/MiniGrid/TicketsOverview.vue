<template>
<div>
    <widget :id="'ticketing-trends'" :title="$tc('phrases.ticketsOverview')">
        <div class="col-sm-12" style="margin: 2vh;">
            <h5>{{ $tc('phrases.ticketsOverview',2) }}</h5>
            <GChart
                type="ColumnChart"
                :data="revenueService.openedTicketChartData"
                :options="chartOptions"
                :resizeDebounce="500"
            />


        </div>


    </widget>
</div>
</template>

<script>
import { RevenueService } from '@/services/RevenueService'
import Widget from '../../shared/widget'

export default {
    name: 'TicketsOverview',
    components: { Widget },
    props:{
        chartOptions:{
            required: true,
        },
        miniGridId:{
            required: true
        }
    },
    data(){
        return{
            revenueService: new RevenueService(),
        }
    },
    methods:{
        async getTicketsData () {
            try {
                await this.revenueService.getTicketsData(this.miniGridId)
            } catch (e) {
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
        },
    }
}
</script>

<style scoped>

</style>
