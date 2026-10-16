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
import { CustomerService } from "../../services/CustomerService.js"
import { MeterModelService } from "../../services/MeterModelService.js"
import { SiteService } from "../../services/SiteService.js"
import { SyncSettingService } from "../../services/SyncSettingService.js"
import { TariffService } from "../../services/TariffService.js"

import Credential from "./Credential.vue"

import { notify } from "@/mixins/notify.js"
import Box from "@/shared/Box.vue"

export default {
  name: "Overview",
  mixins: [notify],
  components: { Credential, Box },
  data() {
    return {
      customerService: new CustomerService(),
      meterModelService: new MeterModelService(),
      tariffService: new TariffService(),
      siteService: new SiteService(),
      syncSettingService: new SyncSettingService(),
    }
  },
  mounted() {
    this.getCustomersCount()
    this.getMeterModelsCount()
    this.getTariffsCount()
    this.getSitesCount()
    this.notifyIfResyncNeeded()
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
    // The detailed per-entity banner/retry lives on that entity's own page now;
    // this is just a heads-up to go look.
    async notifyIfResyncNeeded() {
      const needsResync = await this.syncSettingService.getNeedsResync()
      if (Array.isArray(needsResync) && needsResync.length > 0) {
        const names = needsResync.map((entry) => entry.actionName).join(", ")
        this.alertNotify(
          "warn",
          `${names} didn't sync cleanly — resync from their own pages.`,
        )
      }
    },
  },
}
</script>

<style scoped lang="scss">
.overview-line {
  margin-top: 1rem;
}
</style>
