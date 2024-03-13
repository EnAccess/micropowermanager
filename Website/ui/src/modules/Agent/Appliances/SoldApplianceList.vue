<template>
    <widget
        :class="'col-sm-6 col-md-5'"
        :button="false"
        :title="$tc('phrases.soldAppliances')"
        :button-color="'red'"
        :paginator="agentSoldApplianceService.paginator"
        :subscriber="subscriber"
        color="green"
    >
        <md-table>
            <md-table-row>
                <md-table-head v-for="(item, index) in headers" :key="index">
                    {{ item }}
                </md-table-head>
            </md-table-row>

            <md-table-row
                v-for="(item, index) in agentSoldApplianceService.list"
                :key="index"
            >
                <md-table-cell md-label="ID" md-sort-by="name">
                    {{ item.id }}
                </md-table-cell>
                <md-table-cell md-label="Appliance" md-sort-by="applianceName">
                    {{ item.applianceName }}
                </md-table-cell>
                <md-table-cell md-label="Amount" md-sort-by="amount">
                    {{ item.amount }}
                </md-table-cell>
                <md-table-cell md-label="Customer" md-sort-by="customerName">
                    {{ item.customerName }}
                </md-table-cell>
                <md-table-cell md-label="Sold Date" md-sort-by="createdAt">
                    {{ item.createdAt }}
                </md-table-cell>
            </md-table-row>
        </md-table>
    </widget>
</template>
<script>
import Widget from '../../../shared/widget'
import { AgentSoldApplianceService } from '@/services/AgentSoldApplianceService'
import { EventBus } from '@/shared/eventbus'

export default {
    name: 'SoldApplianceList',
    data() {
        return {
            subscriber: 'agent-sold-appliances',
            agentSoldApplianceService: new AgentSoldApplianceService(
                this.agentId,
            ),
            headers: [
                this.$tc('words.id'),
                this.$tc('words.appliance'),
                this.$tc('words.amount'),
                this.$tc('words.customer'),
                this.$tc('phrases.soldDate'),
            ],
            tableName: 'Sold Appliance',
        }
    },
    components: {
        Widget,
    },
    props: {
        agentId: {
            default: null,
        },
    },
    mounted() {
        EventBus.$on('pageLoaded', this.reloadList)
    },
    beforeDestroy() {
        EventBus.$off('pageLoaded', this.reloadList)
    },
    methods: {
        reloadList(subscriber, data) {
            if (subscriber !== this.subscriber) return
            this.agentSoldApplianceService.updateList(data)
            EventBus.$emit(
                'widgetContentLoaded',
                this.subscriber,
                this.agentSoldApplianceService.list.length,
            )
        },
    },
}
</script>
<style scoped></style>
