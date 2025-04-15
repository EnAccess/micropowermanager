<template>
  <div>
    <new-receipt :addNewReceipt="showNewReceipt" :agent="agent" />
    <widget
      :class="'col-sm-6 col-md-5'"
      :button-text="$tc('phrases.addReceipt', 0)"
      :button="true"
      :title="$tc('words.receipt')"
      @widgetAction="newReceipt"
      :paginator="agentReceiptService.paginator"
      :subscriber="subscriber"
      :resetKey="resetKey"
      color="green"
    >
      <md-table md-sort="id" md-sort-order="asc">
        <md-table-row>
          <md-table-head v-for="(item, index) in headers" :key="index">
            {{ item }}
          </md-table-head>
        </md-table-row>
        <md-table-row
          v-for="(item, index) in agentReceiptService.list"
          :key="index"
        >
          <md-table-cell md-sort-by="id" md-label="ID">
            {{ item.id }}
          </md-table-cell>
          <md-table-cell md-label="Amount">
            {{ moneyFormat(item.amount) }}
          </md-table-cell>
          <md-table-cell md-label="Amount">
            {{ item.receiver }}
          </md-table-cell>
          <md-table-cell md-label="Date">
            {{ item.createdAt }}
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
  </div>
</template>
<script>
import Widget from "@/shared/Widget.vue"
import NewReceipt from "./NewReceipt"
import { AgentReceiptService } from "@/services/AgentReceiptService"
import { EventBus } from "@/shared/eventbus"
import { AgentService } from "@/services/AgentService"
import { notify } from "@/mixins/notify"

export default {
  name: "AgentReceiptList",
  mixins: [notify],
  data() {
    return {
      subscriber: "agent-receipts",
      showNewReceipt: false,
      agent: {},
      agentReceiptService: new AgentReceiptService(this.agentId),
      agentService: new AgentService(),
      resetKey: 0,
      headers: [
        this.$tc("words.id"),
        this.$tc("words.amount"),
        this.$tc("words.receiver"),
        this.$tc("words.date"),
      ],
      tableName: "Agent Receipt",
    }
  },
  components: {
    NewReceipt,
    Widget,
  },
  props: {
    agentId: {
      default: null,
    },
  },
  mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
    EventBus.$on("receiptAdded", this.closeNewReceipt)
    EventBus.$on("newReceiptClosed", () => {
      this.showNewReceipt = false
    })
  },
  beforeDestroy() {
    EventBus.$off("receiptAdded", this.closeNewReceipt)
    EventBus.$off("pageLoaded", this.reloadList)
  },
  methods: {
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) return
      this.agentReceiptService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.agentReceiptService.list.length,
      )
    },
    async closeNewReceipt() {
      this.showNewReceipt = false
      this.resetKey += 1
    },
    async newReceipt() {
      await this.getAgentDetail()
      this.showNewReceipt = true
    },
    async getAgentDetail() {
      try {
        this.agent = await this.agentService.getAgent(this.agentId)
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
  },
}
</script>
<style scoped></style>
<style scoped></style>
