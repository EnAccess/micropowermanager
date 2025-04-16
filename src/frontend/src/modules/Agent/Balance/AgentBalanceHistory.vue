<template>
  <div>
    <add-agent-balance :addNewBalance="showNewBalance" :agent-id="agentId" />
    <widget
      :class="'col-sm-6 col-md-5'"
      :button-text="$tc('phrases.addBalance')"
      :button="true"
      :title="$tc('phrases.balanceHistories')"
      :button-color="'red'"
      @widgetAction="showAddBalance"
      :paginator="agentBalanceHistoryService.paginator"
      :subscriber="subscriber"
      :resetKey="resetKey"
      :show_per_page="true"
      color="green"
    >
      <div>
        <md-table md-sort="id" md-sort-order="asc">
          <md-table-row>
            <md-table-head v-for="(item, index) in headers" :key="index">
              {{ item }}
            </md-table-head>
          </md-table-row>
          <md-table-row
            v-for="(item, index) in agentBalanceHistoryService.list"
            :key="index"
          >
            <md-table-cell md-sort-by="id" md-label="ID">
              {{ item.id }}
            </md-table-cell>
            <md-table-cell md-label="Type">
              {{ item.type }}
            </md-table-cell>
            <md-table-cell md-label="Amount">
              {{ moneyFormat(item.amount) }}
            </md-table-cell>
            <md-table-cell md-label="Date">
              {{ item.createdAt }}
            </md-table-cell>
          </md-table-row>
        </md-table>
      </div>
    </widget>
  </div>
</template>
<script>
import { AgentService } from "@/services/AgentService"
import { AgentBalanceHistoryService } from "@/services/AgentBalanceHistoryService"
import Widget from "@/shared/Widget.vue"
import { EventBus } from "@/shared/eventbus"
import AddAgentBalance from "./AddBalance"
import { notify } from "@/mixins/notify"
import { currency } from "@/mixins/currency"

export default {
  name: "agentBalanceHistoryList",
  mixins: [notify, currency],
  data() {
    return {
      subscriber: "agent-balance-histories",
      agentService: new AgentService(),
      agentBalanceHistoryService: new AgentBalanceHistoryService(this.agentId),
      showNewBalance: false,
      agent: {},
      newBalance: {},
      loading: false,
      resetKey: 0,
      headers: [
        this.$tc("words.id"),
        this.$tc("words.type"),
        this.$tc("words.amount"),
        this.$tc("words.date"),
      ],
      tableName: "Agent Balance History",
    }
  },
  props: {
    agentId: {
      default: null,
    },
  },

  mounted() {
    EventBus.$on("balanceAdded", () => {
      this.showNewBalance = false
      this.resetKey += 1
    })

    EventBus.$on("addBalanceClosed", () => {
      this.showNewBalance = false
    })
    EventBus.$on("receiptAdded", () => {
      this.resetKey += 1
    })
    EventBus.$on("pageLoaded", this.reloadList)
  },
  beforeDestroy() {
    EventBus.$off("addBalanceClosed", () => {
      this.showNewBalance = false
    })
    EventBus.$off("pageLoaded", this.reloadList)
  },
  components: {
    AddAgentBalance,
    Widget,
  },
  methods: {
    showAddBalance() {
      this.showNewBalance = true
    },
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) return
      this.agentBalanceHistoryService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.agentBalanceHistoryService.list.length,
      )
    },
    async saveBalance() {
      let validator = await this.$validator("Balance-Form")
      if (validator) {
        console.log(validator)
      }
    },
    hide() {
      this.showNewAppliance = false
    },
  },
}
</script>
<style scoped></style>
