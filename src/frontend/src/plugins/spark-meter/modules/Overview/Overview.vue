<template>
  <div>
    <div class="overview-line" v-if="needsResync.length > 0">
      <md-card class="resync-banner">
        <md-card-content>
          <div class="resync-header">
            <md-icon class="resync-icon">warning</md-icon>
            <span>
              Some data didn't sync cleanly and may be out of date. Resync the
              affected items below.
            </span>
          </div>
          <div
            v-for="entry in needsResync"
            :key="entry.actionName"
            class="resync-row"
          >
            <span>
              {{ entry.actionName }} ({{ entry.attempts }}/{{
                entry.maxAttempts
              }}
              failed attempts)
            </span>
            <md-button
              class="md-raised md-dense"
              :disabled="resyncing === entry.actionName"
              @click="resyncNow(entry.actionName)"
            >
              Resync now
            </md-button>
          </div>
        </md-card-content>
      </md-card>
    </div>
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
import CustomerRepository from "../../repositories/CustomerRepository.js"
import MeterModelRepository from "../../repositories/MeterModelRepository.js"
import SalesAccountRepository from "../../repositories/SalesAccountRepository.js"
import SiteRepository from "../../repositories/SiteRepository.js"
import TariffRepository from "../../repositories/TariffRepository.js"
import TransactionRepository from "../../repositories/TransactionRepository.js"
import { CustomerService } from "../../services/CustomerService.js"
import { MeterModelService } from "../../services/MeterModelService.js"
import { SiteService } from "../../services/SiteService.js"
import { SyncSettingService } from "../../services/SyncSettingService.js"
import { TariffService } from "../../services/TariffService.js"

import Credential from "./Credential.vue"

import { notify } from "@/mixins/notify.js"
import Box from "@/shared/Box.vue"

// Maps a sync action name to the repository that can resync it, so the "needs
// resync" banner can retrigger exactly the thing that failed.
const RESYNC_REPOSITORIES = {
  Sites: SiteRepository,
  MeterModels: MeterModelRepository,
  Tariffs: TariffRepository,
  SalesAccounts: SalesAccountRepository,
  Customers: CustomerRepository,
  Transactions: TransactionRepository,
}

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
      needsResync: [],
      resyncing: null,
    }
  },
  mounted() {
    this.getCustomersCount()
    this.getMeterModelsCount()
    this.getTariffsCount()
    this.getSitesCount()
    this.getNeedsResync()
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
    async getNeedsResync() {
      this.needsResync = (await this.syncSettingService.getNeedsResync()) || []
    },
    async resyncNow(actionName) {
      const repository = RESYNC_REPOSITORIES[actionName]
      if (!repository) {
        return
      }
      try {
        this.resyncing = actionName
        await repository.sync()
        await this.getNeedsResync()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
      this.resyncing = null
    },
  },
}
</script>

<style scoped lang="scss">
.overview-line {
  margin-top: 1rem;
}

.resync-banner {
  border-left: 4px solid #f9a825;
}

.resync-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.resync-icon {
  color: #f9a825;
}

.resync-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.25rem 0;
}
</style>
