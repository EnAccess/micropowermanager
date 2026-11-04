<template>
  <div>
    <div class="widget-line">
      <widget color="primary" :title="$tc('phrases.stsTokenService')">
        <md-card>
          <md-card-content>
            <md-list class="md-double-line">
              <md-list-item>
                <div class="md-list-item-text">
                  <span>{{ $tc("words.service") }}</span>
                  <span v-if="status">
                    {{ status.name }} {{ status.version }}
                  </span>
                  <span v-else-if="statusError" class="error-text">
                    {{ statusError }}
                  </span>
                  <span v-else>{{ $tc("phrases.stsChecking") }}</span>
                </div>
              </md-list-item>
            </md-list>
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
            <md-field>
              <label>{{ $tc("phrases.stsKeyFile") }}</label>
              <md-file accept=".csv,text/csv" @md-change="selectFile" />
            </md-field>
            <p v-if="result">
              {{
                $t("phrases.stsImportResult", {
                  imported: result.imported,
                  skipped: result.skipped.length,
                })
              }}
            </p>
            <md-table v-if="result && result.skipped.length">
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
          <md-card-actions md-alignment="left">
            <md-button
              class="md-primary"
              :disabled="!file || importing"
              @click="importKeys"
            >
              <md-icon>vpn_key</md-icon>
              {{ $tc("phrases.stsImportKeys") }}
            </md-button>
          </md-card-actions>
        </md-card>
      </widget>
    </div>

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
import { StsMeterVendService } from "../../services/StsMeterVendService.js"

import { notify } from "@/mixins/notify.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "Overview",
  components: { Widget },
  mixins: [notify],
  data() {
    return {
      service: new StsMeterVendService(),
      status: null,
      statusError: null,
      file: null,
      importing: false,
      result: null,
    }
  },
  async mounted() {
    try {
      this.status = await this.service.getStatus()
    } catch (e) {
      this.statusError = e.message
    }
  },
  methods: {
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
    selectFile(files) {
      this.file = files && files.length ? files[0] : null
    },
    async importKeys() {
      this.importing = true
      this.result = null
      try {
        this.result = await this.service.importKeys(this.file)
      } catch (e) {
        this.alertNotify("error", e.message)
      } finally {
        this.importing = false
      }
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

ul {
  margin: 0;
  padding-left: 1.25rem;
}
</style>
