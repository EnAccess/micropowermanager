<template>
  <div>
    <div class="overview-line">
      <div class="md-layout md-gutter">
        <div
          class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-25"
        >
          <box
            :box-color="'blue'"
            :center-text="true"
            :header-text="'Sites'"
            :sub-text="siteService.count.toString()"
            :box-icon="'settings_input_component'"
          />
        </div>
        <div
          class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-25"
        >
          <box
            :box-color="'red'"
            :center-text="true"
            :header-text="'Meters'"
            :sub-text="meterService.count.toString()"
            :box-icon="'settings_input_hdmi'"
          />
        </div>
        <div
          class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-25"
        >
          <box
            :box-color="'green'"
            :center-text="true"
            :header-text="'Customers'"
            :sub-text="customerService.count.toString()"
            :box-icon="'supervisor_account'"
          />
        </div>
        <div
          class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-25"
        >
          <box
            :box-color="'orange'"
            :center-text="true"
            :header-text="'Agents'"
            :sub-text="agentService.count.toString()"
            :box-icon="'support_agent'"
          />
        </div>
      </div>

      <div class="overview-line">
        <div class="md-layout md-gutter">
          <div
            class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-100"
          >
            <credential style="height: 100% !important" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Box from "@/shared/Box.vue"
import Credential from "./Credential"
import { CustomerService } from "../../services/CustomerService"
import { MeterService } from "../../services/MeterService"
import { SiteService } from "../../services/SiteService"
import { AgentService } from "../../services/AgentService"

export default {
  name: "Overview",
  components: { Credential, Box },
  data() {
    return {
      customerService: new CustomerService(),
      meterService: new MeterService(),
      siteService: new SiteService(),
      agentService: new AgentService(),
    }
  },
  mounted() {
    this.getCustomersCount()
    this.getMetersCount()
    this.getSitesCount()
    this.getAgentCount()
  },
  methods: {
    async getCustomersCount() {
      await this.customerService.getCustomersCount()
    },
    async getMetersCount() {
      await this.meterService.getMetersCount()
    },
    async getSitesCount() {
      await this.siteService.getSitesCount()
    },
    async getAgentCount() {
      await this.agentService.getAgentsCount()
    },
  },
}
</script>

<style scoped>
.overview-line {
  margin-top: 1rem;
}
</style>
