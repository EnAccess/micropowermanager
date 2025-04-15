<template>
  <div>
    <AddAgent :addAgent="addNewAgent" />
    <widget
      :title="$tc('words.agent')"
      :search="true"
      :subscriber="subscriber"
      :button="true"
      :button-text="$tc('phrases.newAgent')"
      @widgetAction="showAddNewAgent"
      :paginator="agentService.paginator"
      color="green"
    >
      <md-table>
        <md-table-row>
          <md-table-head v-for="(item, index) in headers" :key="index">
            {{ item }}
          </md-table-head>
        </md-table-row>
        <md-table-row
          v-for="agent in agentService.list"
          style="cursor: pointer"
          :key="agent.id"
          @click="detail(agent.id)"
        >
          <md-table-cell>{{ agent.name }}</md-table-cell>
          <md-table-cell>{{ agent.email }}</md-table-cell>
          <md-table-cell>{{ agent.miniGrid }}</md-table-cell>
          <md-table-cell>{{ agent.balance }}</md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
  </div>
</template>
<script>
import { EventBus } from "@/shared/eventbus"
import Widget from "@/shared/Widget.vue"
import { AgentService } from "@/services/AgentService"
import AddAgent from "@/modules/Agent/NewAgent"
import { notify } from "@/mixins/notify"

export default {
  name: "AgentList",
  mixins: [notify],
  components: { Widget, AddAgent },
  data() {
    return {
      subscriber: "agent-list",
      addNewAgent: false,
      agentService: new AgentService(),
      searchTerm: "",
      headers: [
        this.$tc("words.name"),
        this.$tc("words.email"),
        this.$tc("words.miniGrid"),
        this.$tc("words.balance"),
      ],
      tableName: "Agent",
    }
  },

  mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
    EventBus.$on("searching", this.searching)
    EventBus.$on("end_searching", this.endSearching)
    EventBus.$on("agentAdded", () => {
      this.agentService.showAll()
    })
    EventBus.$on("closed", () => {
      this.addNewAgent = false
    })
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
    EventBus.$off("searching", this.searching)
    EventBus.$off("end_searching", this.endSearching)
  },

  methods: {
    showAddNewAgent() {
      this.addNewAgent = true
    },
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) {
        return
      }
      this.agentService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.agentService.list.length,
      )
    },
    detail(id) {
      this.$router.push({ path: "/agents/" + id })
    },
    searching(searchTerm) {
      this.agentService.search(searchTerm)
    },
    endSearching() {
      this.agentService.showAll()
    },
    clearSearch() {
      this.searchTerm = ""
    },
  },
}
</script>

<style lang="scss" scoped>
.md-app {
  min-height: 100vh;
  border: 1px solid rgba(#000, 0.12);
}

// Demo purposes only
.md-drawer {
  width: 230px;
  max-width: calc(100vw - 125px);
}
</style>
