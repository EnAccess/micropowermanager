<template>
  <div>
    <assign-appliance
      :assignNewAppliance="showNewAppliance"
      :agent-id="agentId"
    />
    <widget
      :class="'col-sm-6 col-md-5'"
      :button-text="$tc('phrases.assignAppliance', 0)"
      :button="true"
      :title="$tc('phrases.assignAppliance', 1)"
      color="green"
      :subscriber="subscriber"
      @widgetAction="addNewAppliance"
    >
      <div>
        <!-- ana tablo  -->

        <md-table>
          <md-table-row>
            <md-table-head>{{ $tc("words.name") }}</md-table-head>
            <md-table-head>{{ $tc("words.cost") }}</md-table-head>
          </md-table-row>
          <md-table-row
            v-for="(item, index) in assignedApplianceTypes"
            :key="index"
          >
            <md-table-cell md-label="Name" md-sort-by="name">
              {{ item.appliance.name }}
            </md-table-cell>
            <md-table-cell md-label="Cost" md-sort-by="total_cost">
              {{ moneyFormat(item.cost) }}
            </md-table-cell>
          </md-table-row>
        </md-table>
      </div>
    </widget>
  </div>
</template>
<script>
import Widget from "@/shared/Widget.vue"
import { AgentAssignedApplianceService } from "@/services/AgentAssignedApplianceService"
import { AgentService } from "@/services/AgentService"
import AssignAppliance from "./AssignAppliance"
import { EventBus } from "@/shared/eventbus"
import { currency } from "@/mixins/currency"
import { notify } from "@/mixins/notify"

export default {
  name: "AssignedApplianceList",
  mixins: [currency, notify],
  data() {
    return {
      assignedApplianceService: new AgentAssignedApplianceService(),
      agentService: new AgentService(),
      subscriber: "assigned-appliance-list",
      showNewAppliance: false,
      agent: {},
      newAppliance: {
        id: null,
        name: null,
        cost: null,
      },
      loading: false,
      assignedApplianceTypes: [],
      applianceTypes: [],
    }
  },
  props: {
    agentId: {
      default: null,
    },
  },
  mounted() {
    this.getAgentDetail()
    this.getAssignedAppliances(this.agentId)

    EventBus.$on("applianceAssigned", this.closeAssignAppliance)
    EventBus.$on("assignApplianceClosed", () => {
      this.showNewAppliance = false
    })
  },
  destroyed() {
    EventBus.$off("applianceAssigned", this.closeAssignAppliance)
  },
  components: {
    AssignAppliance,
    Widget,
  },
  methods: {
    addNewAppliance() {
      this.showNewAppliance = true
    },

    async closeAssignAppliance() {
      this.showNewAppliance = false

      if (this.agent.id !== undefined) {
        await this.getAssignedAppliances(this.agent.id)
      }
    },
    async getAgentDetail() {
      try {
        this.agent = await this.agentService.getAgent(Number(this.agentId))
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getAssignedAppliances(agentId) {
      try {
        this.assignedApplianceTypes =
          await this.assignedApplianceService.getAssignedAppliances(agentId)
        EventBus.$emit(
          "widgetContentLoaded",
          this.subscriber,
          this.assignedApplianceTypes.length,
        )
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },

    async hide() {
      this.showNewAppliance = false
      await this.getAssignedAppliances(this.agent)
    },
  },
}
</script>
<style scoped></style>
