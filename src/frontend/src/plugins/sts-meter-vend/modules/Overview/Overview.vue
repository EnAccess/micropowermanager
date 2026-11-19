<template>
  <div>
    <div class="widget-line">
      <widget color="primary" :title="$tc('phrases.stsTokenService')">
        <md-card>
          <md-card-content class="engine">
            <img :src="nxtLogo" alt="NXT GRID" class="engine-logo" />
            <div class="engine-details">
              <div class="engine-title">
                <span v-if="status">{{ status.name }}</span>
                <span v-if="status" class="engine-version">
                  {{ status.version }}
                </span>
                <span
                  class="engine-state"
                  :class="{
                    connected: status,
                    unreachable: statusError,
                  }"
                >
                  <md-icon>{{ connectionIcon }}</md-icon>
                  {{ connectionLabel }}
                </span>
              </div>
              <p v-if="status" class="engine-description">
                {{ status.description }}
              </p>
              <p v-if="statusError" class="error-text">{{ statusError }}</p>
              <p v-if="lastCheckedAt" class="engine-checked">
                {{ $tc("phrases.lastChecked") }}: {{ lastCheckedAt }}
              </p>
            </div>
            <md-button
              class="md-raised engine-check"
              :disabled="checking"
              @click="checkStatus"
            >
              <md-icon>sync</md-icon>
              {{ $tc("phrases.stsCheckConnection") }}
            </md-button>
          </md-card-content>
        </md-card>
      </widget>
    </div>

    <div class="widget-line">
      <widget color="primary" :title="$tc('phrases.stsDecoderKeys')">
        <md-card>
          <md-card-content>
            <p>
              {{
                $t("phrases.stsKeyImportHelp", {
                  serial: "serial",
                  key: "decoder_key",
                })
              }}
            </p>
            <p>
              {{
                $t("phrases.stsKeyProfileHelp", {
                  baseDate: "base_date",
                  algorithm: "encryption_algorithm",
                  keyExpiry: "key_expiry_number",
                })
              }}
            </p>
            <p>
              <a href="#" @click.prevent="downloadKeyTemplate">
                <md-icon>download</md-icon>
                {{ $tc("phrases.stsDownloadKeyTemplate") }}
              </a>
            </p>
            <div class="key-file">
              <input
                ref="keyFileInput"
                type="file"
                accept=".csv,text/csv"
                class="key-file-input"
                @change="importKeys"
              />
              <md-button
                class="md-raised md-primary"
                :disabled="importing"
                @click="$refs.keyFileInput.click()"
              >
                <md-icon>upload_file</md-icon>
                {{ $tc("phrases.stsImportKeys") }}
              </md-button>
              <span v-if="file" class="key-file-name">
                <md-icon>description</md-icon>
                {{ file.name }}
              </span>
            </div>
            <md-progress-bar v-if="importing" md-mode="indeterminate" />
            <p v-if="result">
              {{
                $t("phrases.stsImportResult", {
                  imported: result.imported,
                  skipped: result.skipped.length,
                })
              }}
              <template v-if="result.created">
                {{
                  $t("phrases.stsMetersCreated", { created: result.created })
                }}
              </template>
            </p>
            <md-table
              v-if="result && result.skipped.length"
              class="skipped-rows"
            >
              <md-table-row>
                <md-table-head>{{ $tc("words.serialNumber") }}</md-table-head>
                <md-table-head>{{ $tc("words.reason") }}</md-table-head>
              </md-table-row>
              <md-table-row
                v-for="row in result.skipped"
                :key="row.serial + row.reason"
              >
                <md-table-cell>{{ row.serial }}</md-table-cell>
                <md-table-cell>{{ row.reason }}</md-table-cell>
              </md-table-row>
            </md-table>
          </md-card-content>
        </md-card>
      </widget>
    </div>

    <md-dialog :md-active.sync="showCreateMetersDialog">
      <md-dialog-title>
        {{ $tc("phrases.stsCreateMetersTitle") }}
      </md-dialog-title>
      <md-dialog-content>
        <md-progress-bar v-if="importing" md-mode="indeterminate" />
        <p class="dialog-hint">
          {{
            $t("phrases.stsCreateMetersBody", {
              count: result ? result.unregistered.length : 0,
            })
          }}
        </p>
        <md-field>
          <label>{{ $tc("words.manufacturer") }}</label>
          <md-select v-model="newMeterManufacturerId">
            <md-option
              v-for="manufacturer in manufacturers"
              :key="manufacturer.id"
              :value="manufacturer.id"
            >
              {{ manufacturer.name }}
            </md-option>
          </md-select>
        </md-field>
        <md-field>
          <label>{{ $tc("words.tariff") }}</label>
          <md-select v-model="newMeterTariffId">
            <md-option
              v-for="tariff in tariffs"
              :key="tariff.id"
              :value="tariff.id"
            >
              {{ tariff.name }}
            </md-option>
          </md-select>
        </md-field>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button
          class="md-raised md-primary"
          :disabled="importing || !newMeterManufacturerId || !newMeterTariffId"
          @click="createMeters"
        >
          {{ $tc("phrases.stsCreateMeters") }}
        </md-button>
        <md-button
          :disabled="importing"
          @click="showCreateMetersDialog = false"
        >
          {{ $tc("words.cancel") }}
        </md-button>
      </md-dialog-actions>
    </md-dialog>

    <div class="widget-line">
      <widget color="primary" :title="$tc('phrases.stsOperatingNotes')">
        <md-card>
          <md-card-content>
            <ul>
              <li>{{ $tc("phrases.stsNoteOneWaySwitch") }}</li>
              <li>{{ $tc("phrases.stsNoteOneTokenPerMinute") }}</li>
              <li>{{ $tc("phrases.stsNoteRounding") }}</li>
            </ul>
          </md-card-content>
        </md-card>
      </widget>
    </div>
  </div>
</template>

<script>
import moment from "moment-timezone"

import { StsMeterVendService } from "../../services/StsMeterVendService.js"

import nxtLogo from "@/assets/images/nxt-logo.png"
import { notify } from "@/mixins/notify.js"
import { ManufacturerService } from "@/services/ManufacturerService.js"
import { TariffService } from "@/services/TariffService.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "Overview",
  components: { Widget },
  mixins: [notify],
  data() {
    return {
      service: new StsMeterVendService(),
      nxtLogo,
      status: null,
      statusError: null,
      checking: false,
      lastCheckedAt: null,
      file: null,
      importing: false,
      result: null,
      showCreateMetersDialog: false,
      manufacturers: [],
      tariffs: [],
      newMeterManufacturerId: null,
      newMeterTariffId: null,
    }
  },
  computed: {
    connectionIcon() {
      if (this.checking) return "hourglass_empty"
      return this.status ? "check_circle" : "error"
    },
    connectionLabel() {
      if (this.checking) return this.$tc("phrases.stsChecking")
      return this.$tc(
        this.status ? "phrases.stsConnected" : "phrases.stsUnreachable",
      )
    },
  },
  mounted() {
    this.checkStatus()
  },
  methods: {
    async checkStatus() {
      this.checking = true
      try {
        this.status = await this.service.getStatus()
        this.statusError = null
      } catch (e) {
        this.status = null
        this.statusError = e.message
      } finally {
        this.checking = false
        this.lastCheckedAt = moment().format("YYYY-MM-DD HH:mm:ss")
      }
    },
    async downloadKeyTemplate() {
      try {
        const response = await this.service.downloadKeyTemplate()
        const downloadUrl = window.URL.createObjectURL(
          new Blob([response.data]),
        )
        const link = document.createElement("a")
        link.href = downloadUrl
        link.download = "sts-meter-vend-keys-template.csv"
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(downloadUrl)
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async importKeys(event) {
      const file = event.target.files[0]
      // Cleared so choosing the same file again, e.g. after fixing it, still
      // triggers a change.
      event.target.value = ""
      if (!file) return

      this.file = file
      const imported = await this.runImport()
      if (imported && this.result.unregistered.length) {
        await this.openCreateMetersDialog()
      }
    },
    async runImport(newMeters = {}) {
      this.importing = true
      try {
        this.result = await this.service.importKeys(this.file, newMeters)
        return true
      } catch (e) {
        this.alertNotify("error", e.message)
        return false
      } finally {
        this.importing = false
      }
    },
    async openCreateMetersDialog() {
      try {
        const [manufacturers, tariffs] = await Promise.all([
          new ManufacturerService().getManufacturers("meter"),
          new TariffService().getTariffs(),
        ])
        this.manufacturers = manufacturers
        this.tariffs = tariffs
        this.showCreateMetersDialog = true
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async createMeters() {
      const imported = await this.runImport({
        manufacturerId: this.newMeterManufacturerId,
        tariffId: this.newMeterTariffId,
      })
      if (imported) this.showCreateMetersDialog = false
    },
  },
}
</script>

<style scoped lang="scss">
.widget-line + .widget-line {
  margin-top: 1rem;
}

.error-text {
  color: #b00020;
}

.engine {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 24px;
}

.engine-logo {
  height: 56px;
  width: auto;
}

.engine-details {
  flex: 1 1 240px;
  min-width: 0;

  p {
    margin: 4px 0 0;
  }
}

.engine-title {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  font-size: 18px;
  font-weight: 500;
}

.engine-version {
  color: $brand-primary;
}

.engine-state {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 12px 2px 6px;
  border-radius: 16px;
  font-size: 13px;
  background: $brand-background;

  ::v-deep .md-icon {
    margin: 0;
    color: inherit;
  }

  &.connected {
    color: #2e7d32;
    background: #e8f5e9;
  }

  &.unreachable {
    color: #b00020;
    background: #fdecea;
  }
}

.engine-description,
.engine-checked {
  color: rgba(0, 0, 0, 0.6);
}

.engine-check {
  margin: 0;
}

ul {
  margin: 0;
  padding-left: 1.25rem;
}

.key-file {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  margin: 8px 0 16px;

  .md-button {
    margin: 0;
  }
}

.key-file-input {
  display: none;
}

.skipped-rows ::v-deep {
  .md-table-content {
    max-height: 320px;
    overflow-y: auto;
  }

  .md-table-head {
    position: sticky;
    top: 0;
    z-index: 1;
    background: $brand-white;
  }
}

.dialog-hint {
  max-width: 420px;
}

.key-file-name {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  word-break: break-all;
}
</style>
