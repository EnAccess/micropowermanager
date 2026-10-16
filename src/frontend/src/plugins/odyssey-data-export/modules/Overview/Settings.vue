<template>
  <form @submit.prevent="saveSettings">
    <md-card>
      <md-card-header>Financing Program</md-card-header>
      <md-card-content>
        <p>
          Sent as
          <code>financingId</code>
          with every exported payment. Leave empty if your mini-grids are not
          part of an Odyssey financing program.
        </p>
        <md-field>
          <label for="financingId">Financing program</label>
          <md-select
            id="financingId"
            name="financingId"
            v-model="settingsService.financingId"
          >
            <md-option value="">None</md-option>
            <md-option
              v-for="program in settingsService.financingPrograms"
              :key="program"
              :value="program"
            >
              {{ program }}
            </md-option>
          </md-select>
        </md-field>
      </md-card-content>
      <md-progress-bar md-mode="indeterminate" v-if="loading" />
      <md-card-actions>
        <md-button
          class="md-raised md-primary"
          type="submit"
          :disabled="loading"
        >
          {{ $tc("words.save") }}
        </md-button>
      </md-card-actions>
    </md-card>
  </form>
</template>

<script>
import { SettingsService } from "../../services/SettingsService.js"

import { notify } from "@/mixins/notify.js"

export default {
  name: "OdysseyExportSettings",
  mixins: [notify],
  data() {
    return {
      settingsService: new SettingsService(),
      loading: false,
    }
  },
  mounted() {
    this.loadSettings()
  },
  methods: {
    async loadSettings() {
      try {
        await this.settingsService.getSettings()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async saveSettings() {
      this.loading = true
      try {
        await this.settingsService.updateSettings()
        this.alertNotify("success", "Financing program updated")
      } catch (e) {
        this.alertNotify("error", e.message)
      }
      this.loading = false
    },
  },
}
</script>
