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
            :header-text="'Meter Models'"
            :sub-text="meterModelService.count.toString()"
            :box-icon="'settings_input_hdmi'"
          />
        </div>
        <div
          class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-25"
        >
          <box
            :box-color="'green'"
            :center-text="true"
            :header-text="'Tariffs'"
            :sub-text="tariffService.count.toString()"
            :box-icon="'attach_money'"
          />
        </div>
        <div
          class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-25"
        >
          <box
            :center-text="true"
            :box-color="'orange'"
            :header-text="'Customers'"
            :sub-text="customerService.count.toString()"
            :box-icon="'supervisor_account'"
          />
        </div>
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
</template>

<script>
import Box from "@/shared/Box.vue"
import Credential from "./Credential"
import { CustomerService } from "../../services/CustomerService"
import { MeterModelService } from "../../services/MeterModelService"
import { TariffService } from "../../services/TariffService"
import { SiteService } from "../../services/SiteService"

export default {
  name: "Overview",
  components: { Credential, Box },
  data() {
    return {
      customerService: new CustomerService(),
      meterModelService: new MeterModelService(),
      tariffService: new TariffService(),
      siteService: new SiteService(),
      meterModelsCount: 0,
      tariffsCount: 0,
    }
  },
  mounted() {
    this.getCustomersCount()
    this.getMeterModelsCount()
    this.getTariffsCount()
    this.getSitesCount()
  },
  methods: {
    async getCustomersCount() {
      await this.customerService.getCustomersCount()
    },
    async getMeterModelsCount() {
      await this.meterModelService.getMeterModelsCount()
    },
    async getTariffsCount() {
      await this.tariffService.getTariffsCount()
    },
    async getSitesCount() {
      await this.siteService.getSitesCount()
    },
  },
}
</script>

<style scoped>
.overview-line {
  margin-top: 1rem;
}
</style>
