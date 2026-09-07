<template>
  <div>
    <sell-assigned-appliance
      :agent-id="agentId"
      :show-sell-appliance-modal="showSellApplianceModal"
      @hideModal="showSellApplianceModal = false"
      @applianceSold="onApplianceSold"
    />
    <widget
      :class="'col-sm-6 col-md-5'"
      :button="true"
      :button-text="$tc('phrases.sellAppliance', 0)"
      :title="$tc('phrases.soldAppliances')"
      :button-color="'red'"
      :paginator="agentSoldApplianceService.paginator"
      :subscriber="subscriber"
      color="primary"
      @widgetAction="showSellApplianceModal = true"
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
          @click="showSoldApplianceDetail(item.id)"
          style="cursor: pointer"
        >
          <md-table-cell md-label="ID" md-sort-by="name">
            {{ item.id }}
          </md-table-cell>
          <md-table-cell md-label="Appliance" md-sort-by="applianceName">
            {{ item.applianceName }}
          </md-table-cell>
          <md-table-cell md-label="Amount" md-sort-by="amount">
            {{ amountLabel(item) }}
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
  </div>
</template>
<script>
import SellAssignedAppliance from "./SellAssignedAppliance.vue"

import { currency } from "@/mixins/currency.js"
import { notify } from "@/mixins/notify.js"
import { AgentSoldApplianceService } from "@/services/AgentSoldApplianceService.js"
import { EventBus } from "@/shared/eventbus.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "SoldApplianceList",
  mixins: [currency, notify],
  data() {
    return {
      subscriber: "agent-sold-appliances",
      agentSoldApplianceService: new AgentSoldApplianceService(this.agentId),
      showSellApplianceModal: false,
      headers: [
        this.$tc("words.id"),
        this.$tc("words.appliance"),
        this.$tc("words.amount"),
        this.$tc("words.customer"),
        this.$tc("phrases.soldDate"),
      ],
      tableName: "Sold Appliance",
    }
  },
  components: {
    SellAssignedAppliance,
    Widget,
  },
  props: {
    agentId: {
      default: null,
    },
  },
  mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
  },
  methods: {
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) return
      this.agentSoldApplianceService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.agentSoldApplianceService.list.length,
      )
    },
    amountLabel(item) {
      if (!item.isEnergyService) {
        return this.moneyFormat(item.amount)
      }
      return item.pricePerDay
        ? this.$tc("phrases.perDay", 1, {
            amount: this.moneyFormat(item.pricePerDay),
          })
        : "—"
    },
    async onApplianceSold() {
      this.showSellApplianceModal = false
      try {
        const list = await this.agentSoldApplianceService.reloadList()
        EventBus.$emit("widgetContentLoaded", this.subscriber, list.length)
      } catch (e) {
        this.alertNotify("error", e.message)
      }
      // the sale books a down payment and commission on the agent, which the
      // detail and balance widgets on this page render from their own state
      EventBus.$emit("balanceAdded")
    },
    showSoldApplianceDetail(id) {
      this.$router.push({ path: "/sold-appliance-detail/" + id })
    },
  },
}
</script>
<style scoped lang="scss"></style>
