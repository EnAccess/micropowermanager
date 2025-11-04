<template>
  <div>
    <md-toolbar class="md-dense">
      <h3 class="md-title" style="flex: 1">
        {{ $tc("phrases.agentDashboard") }}
      </h3>
      <md-button
        class="md-raised md-primary"
        @click="showAddAgentModal"
        style="margin-right: 8px"
      >
        <md-icon>add</md-icon>
        {{ $tc("phrases.newAgent") }}
      </md-button>
      <md-button class="md-raised" @click="refreshData" :disabled="loading">
        <md-icon>update</md-icon>
        {{ $tc("phrases.refreshData") }}
        <md-progress-bar
          v-if="loading"
          md-mode="indeterminate"
        ></md-progress-bar>
      </md-button>
    </md-toolbar>
    <new-agent
      :add-agent="showNewAgentModal"
      @agent-added="onAgentAdded"
      v-if="showNewAgentModal"
    />

    <div>
      <div class="md-layout md-gutter" style="margin-top: 3rem">
        <!-- Basic Agent Metrics -->
        <div class="md-layout-item md-size-100">
          <div class="md-layout md-gutter">
            <div
              class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100"
            >
              <box
                :box-color="'blue'"
                :center-text="true"
                :header-text="$tc('phrases.totalAgents')"
                :sub-text="totalAgents.toString()"
                :box-icon="'supervisor_account'"
              />
            </div>
            <div
              class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100"
            >
              <box
                :box-color="'orange'"
                :center-text="true"
                :header-text="$tc('phrases.activeAgents')"
                :sub-text="activeAgents.toString()"
                :box-icon="'check_circle'"
              />
            </div>
            <div
              class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100"
            >
              <box
                :box-color="'green'"
                :center-text="true"
                :header-text="$tc('phrases.totalCustomers')"
                :sub-text="totalCustomers.toString()"
                :box-icon="'people'"
              />
            </div>
            <div
              class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100"
            >
              <box
                :box-color="'red'"
                :center-text="true"
                :header-text="$tc('phrases.totalSales')"
                :sub-text="totalSales.toString()"
                :box-icon="'shopping_cart'"
              />
            </div>
          </div>
        </div>

        <!-- Agent List -->
        <div class="md-layout-item md-size-100">
          <widget :title="$tc('phrases.agentList')" id="agent-list">
            <div class="search-section">
              <md-field>
                <label>{{ $tc("words.search") }}</label>
                <md-input v-model="searchTerm" @input="filterAgents" />
                <md-icon>search</md-icon>
              </md-field>
            </div>

            <md-table md-card style="margin-left: 0">
              <md-table-row>
                <md-table-head>{{ $tc("words.name") }}</md-table-head>
                <md-table-head>{{ $tc("words.email") }}</md-table-head>
                <md-table-head>{{ $tc("words.phone") }}</md-table-head>
                <md-table-head>{{ $tc("words.customers") }}</md-table-head>
                <md-table-head>{{ $tc("words.sales") }}</md-table-head>
                <md-table-head>{{ $tc("words.commission") }}</md-table-head>
                <md-table-head>{{ $tc("words.status") }}</md-table-head>
              </md-table-row>
              <md-table-row
                v-for="agent in filteredAgents"
                :key="agent.id"
                style="cursor: pointer"
                @click="viewAgentDetail(agent.id)"
              >
                <md-table-cell>
                  {{
                    agent.person
                      ? `${agent.person.name} ${agent.person.surname}`
                      : agent.email
                  }}
                </md-table-cell>
                <md-table-cell>{{ agent.email }}</md-table-cell>
                <md-table-cell>
                  {{
                    agent.person &&
                    agent.person.addresses &&
                    agent.person.addresses[0]
                      ? agent.person.addresses[0].phone
                      : "-"
                  }}
                </md-table-cell>
                <md-table-cell>{{ agent.customer_count || 0 }}</md-table-cell>
                <md-table-cell>{{ agent.sales_count || 0 }}</md-table-cell>
                <md-table-cell>
                  {{ formatCurrency(agent.total_commission || 0) }}
                </md-table-cell>
                <md-table-cell>
                  <md-chip
                    :class="agent.is_active ? 'md-primary' : 'md-default'"
                  >
                    {{
                      agent.is_active
                        ? $tc("words.active")
                        : $tc("words.inactive")
                    }}
                  </md-chip>
                </md-table-cell>
              </md-table-row>
            </md-table>
          </widget>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Box from "@/shared/Box.vue"
import Widget from "@/shared/Widget.vue"
import NewAgent from "@/modules/Agent/NewAgent.vue"
import { AgentDashboardService } from "@/services/AgentDashboardService"
import { notify } from "@/mixins/notify"
import { currency } from "@/mixins/currency"
import { EventBus } from "@/shared/eventbus"

export default {
  name: "AgentDashboard",
  components: {
    Box,
    Widget,
    NewAgent,
  },
  mixins: [notify, currency],
  data() {
    return {
      loading: false,
      searchTerm: "",
      agentDashboardService: new AgentDashboardService(),
      agents: [],
      agentMetrics: {},
      showNewAgentModal: false,
    }
  },
  computed: {
    totalAgents() {
      return this.agents.length
    },
    activeAgents() {
      return this.agents.filter((agent) => agent.is_active).length
    },
    totalCustomers() {
      return this.agents.reduce(
        (sum, agent) => sum + (agent.customer_count || 0),
        0,
      )
    },
    totalSales() {
      return this.agents.reduce(
        (sum, agent) => sum + (agent.sales_count || 0),
        0,
      )
    },
    filteredAgents() {
      if (!this.searchTerm) return this.agents

      const searchLower = this.searchTerm.toLowerCase()
      return this.agents.filter((agent) => {
        const name = agent.person
          ? `${agent.person.name} ${agent.person.surname}`
          : ""
        const email = agent.email || ""
        return (
          name.toLowerCase().includes(searchLower) ||
          email.toLowerCase().includes(searchLower)
        )
      })
    },
  },
  mounted() {
    this.loadAgentData()
  },
  methods: {
    async loadAgentData() {
      this.loading = true
      try {
        // Load both agent list and performance metrics
        const [agentsResponse, metricsResponse] = await Promise.all([
          this.agentDashboardService.getAgentList(),
          this.agentDashboardService.getAgentPerformanceMetrics(),
        ])

        this.agents = agentsResponse || []
        this.agentMetrics = metricsResponse?.data?.metrics || {}
      } catch (error) {
        this.alertNotify("error", "Failed to load agent data")
      } finally {
        this.loading = false
      }
    },
    async refreshData() {
      await this.loadAgentData()
      this.alertNotify("success", "Agent data refreshed successfully")
    },
    filterAgents() {
      // Filtering is handled by computed property
    },
    viewAgentDetail(agentId) {
      this.$router.push({ path: `/agents/${agentId}` })
    },
    formatCurrency(amount) {
      const currency =
        this.$store.getters["settings/getMainSettings"]?.currency || "TSZ"
      return this.readable(amount) + currency
    },
    // Add these new methods
    showAddAgentModal() {
      this.showNewAgentModal = true
    },
    onAgentAdded() {
      this.showNewAgentModal = false
      this.loadAgentData() // Refresh the list
      this.alertNotify("success", this.$tc("phrases.agentCreatedSuccess"))
    },
  },
  created() {
    // Listen for the close event from NewAgent
    EventBus.$on("closed", () => {
      this.showNewAgentModal = false
    })
  },
  beforeDestroy() {
    // Clean up event listener
    EventBus.$off("closed")
  },
}
</script>

<style lang="scss" scoped>
.search-section {
  margin-bottom: 1rem;
  padding: 0 1rem;
}

.md-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.md-toolbar .md-title {
  flex: 1;
}

.md-toolbar .md-button {
  margin-left: auto;
}

.md-chip.md-primary {
  background-color: #4caf50 !important;
  color: white !important;
}

.md-chip.md-default {
  background-color: #9e9e9e !important;
  color: white !important;
}

.md-button {
  min-width: 120px;

  .md-icon {
    margin-right: 4px;
  }
}
</style>
