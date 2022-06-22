<template>
<div>
    <widget :id="'revenue-trends'" :title="$tc('phrases.revenueTrends')"
            :subscriber="subscriber"
    >
        <div class="md-layout md-gutter">
            <div class="md-layout-item md-medium-size-100 md-xsmall-size-100 md-size-100">
                <GChart
                    type="ColumnChart"
                    :data="revenueService.trendChartData.base"
                    :options="chartOptions"
                    :resizeDebounce="500"
                />
            </div>
            <div class="md-layout-item md-medium-size-100 md-xsmall-size-100 md-size-100">
                <GChart
                    type="LineChart"
                    :data="revenueService.trendChartData.overview"
                    :options="chartOptions"
                    :resizeDebounce="500"
                />
            </div>
        </div>


    </widget>
    <redirection-modal :redirection-url="redirectionUrl" :imperative-item="imperativeItem"
                       :dialog-active="redirectDialogActive"/>
</div>
</template>

<script>
import Widget from '../../shared/widget'
import { EventBus } from '@/shared/eventbus'
import { RevenueService } from '@/services/RevenueService'
import RedirectionModal from '../../shared/RedirectionModal'

export default {
    name: 'RevenueTrends',
    components: { RedirectionModal, Widget },
    props:{
        chartOptions:{
            required: true
        },
        miniGridId: {
            required: true
        }

    },
    data(){
        return{
            revenueService: new RevenueService(),
            redirectionUrl: '/locations/add-village',
            imperativeItem: 'City',
            redirectDialogActive: false,
            subscriber: 'miniGrid-revenue-trends'

        }
    },
    methods:{
        async getRevenueTrends(startDate, endDate, tab){
            try {
                await this.revenueService.getMiniGridRevenueTrends(this.miniGridId, startDate, endDate, tab)
                if (!this.revenueService.revenueTrends) {
                    this.redirectDialogActive = true
                }
                EventBus.$emit('widgetContentLoaded', this.subscriber, Object.keys(this.revenueService.revenueTrends).length)
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
