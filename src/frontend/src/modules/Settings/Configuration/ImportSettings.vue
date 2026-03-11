<template>
  <div>
    <md-card>
      <md-card-header>
        <div class="md-title">Import Data</div>
      </md-card-header>

      <md-card-content>
        <md-table>
          <md-table-head>Entity</md-table-head>
          <md-table-head>{{ $tc("words.description") }}</md-table-head>
          <md-table-head>Action</md-table-head>

          <md-table-row
            v-for="entity in importEntities"
            :key="entity.key"
          >
            <md-table-cell>
              <div class="entity-info">
                <md-icon class="entity-icon">{{ entity.icon }}</md-icon>
                <span>{{ entity.label }}</span>
              </div>
            </md-table-cell>
            <md-table-cell>
              <span class="entity-description">{{ entity.description }}</span>
            </md-table-cell>
            <md-table-cell>
              <div class="action-cell">
                <input
                  type="file"
                  @change="handleFileSelect($event, entity.key)"
                  accept=".json"
                  :ref="`${entity.key}-file-input`"
                  style="display: none"
                />
                <md-button
                  v-if="!selectedFiles[entity.key] && !loadingStates[entity.key]"
                  class="md-primary md-raised md-dense"
                  @click="triggerFileInput(entity.key)"
                >
                  <md-icon>file_upload</md-icon>
                  {{ $tc("words.select") }} {{ $tc("words.file") }}
                </md-button>
                <div
                  v-else-if="selectedFiles[entity.key] && !loadingStates[entity.key]"
                  class="file-selected"
                >
                  <md-icon class="file-icon">insert_drive_file</md-icon>
                  <span class="file-name">
                    {{ selectedFileNames[entity.key] }}
                  </span>
                  <md-button
                    class="md-icon-button md-dense"
                    @click="clearFile(entity.key)"
                  >
                    <md-icon>close</md-icon>
                  </md-button>
                </div>
                <md-progress-spinner
                  v-else
                  md-diameter="20"
                  md-stroke="2"
                ></md-progress-spinner>
              </div>
            </md-table-cell>
          </md-table-row>
        </md-table>

        <div v-if="pollingJobId" class="import-progress">
          <md-progress-bar md-mode="indeterminate"></md-progress-bar>
          <p class="import-progress-text">
            Import is being processed in the background...
          </p>
        </div>

        <div class="import-actions" v-if="hasSelectedFiles && !pollingJobId">
          <md-button class="md-raised" @click="clearAllFiles">
            Clear All
          </md-button>
          <md-button
            class="md-primary md-raised"
            @click="importAll"
            :disabled="importing"
          >
            <md-icon>cloud_upload</md-icon>
            Import Selected
          </md-button>
        </div>
      </md-card-content>
    </md-card>

    <md-dialog :md-active.sync="showResultDialog">
      <md-dialog-title>{{ resultDialogTitle }}</md-dialog-title>
      <md-dialog-content>
        <div v-if="importResult">
          <div class="result-stats">
            <p>
              <strong>Successful:</strong>
              {{ importResult.success_count }}
            </p>
            <p v-if="importResult.failed_count > 0">
              <strong>Failed:</strong>
              {{ importResult.failed_count }}
            </p>
          </div>
          <div
            v-if="importResult.results && importResult.results.length > 0"
            class="result-details"
          >
            <p><strong>Details:</strong></p>
            <ul>
              <li v-for="(r, idx) in importResult.results" :key="idx">
                <span :class="r.success ? 'result-success' : 'result-failed'">
                  {{ entityLabel(r.type) }}:
                  {{ r.success ? "Imported successfully" : r.error }}
                </span>
              </li>
            </ul>
          </div>
        </div>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button class="md-primary" @click="showResultDialog = false">
          {{ $tc("words.close") }}
        </md-button>
      </md-dialog-actions>
    </md-dialog>
  </div>
</template>

<script>
import { notify } from "@/mixins/notify.js"
import { ApplianceImportService } from "@/services/ApplianceImportService.js"
import { ClusterImportService } from "@/services/ClusterImportService.js"
import { CustomerImportService } from "@/services/CustomerImportService.js"
import { DeviceImportService } from "@/services/DeviceImportService.js"
import { ImportStatusService } from "@/services/ImportStatusService.js"
import { SettingsImportService } from "@/services/SettingsImportService.js"
import { TransactionImportService } from "@/services/TransactionImportService.js"
import { UserPermissionImportService } from "@/services/UserPermissionImportService.js"
import { EventBus } from "@/shared/eventbus.js"

const POLL_INTERVAL_MS = 2000

const IMPORT_ENTITIES = [
  {
    key: "settings",
    label: "Settings",
    icon: "settings",
    description: "Import system settings from exported JSON file",
  },
  {
    key: "user_permissions",
    label: "User Permissions",
    icon: "people",
    description: "Import user permissions and roles from exported JSON file",
  },
  {
    key: "clusters",
    label: "Clusters",
    icon: "location_city",
    description:
      "Import clusters with mini-grids and villages from exported JSON file",
  },
  {
    key: "devices",
    label: "Devices",
    icon: "devices",
    description:
      "Import meters, solar home systems and e-bikes from exported JSON file",
  },
  {
    key: "customers",
    label: "Customers",
    icon: "person",
    description:
      "Import customers with addresses and contact info from exported JSON file",
  },
  {
    key: "transactions",
    label: "Transactions",
    icon: "receipt",
    description: "Import transactions from exported JSON file",
  },
  {
    key: "appliances",
    label: "Appliances",
    icon: "kitchen",
    description:
      "Import appliances with types and payment plans from exported JSON file",
  },
]

export default {
  name: "ImportSettings",
  mixins: [notify],
  data() {
    const selectedFiles = {}
    const selectedFileNames = {}
    const loadingStates = {}
    IMPORT_ENTITIES.forEach((e) => {
      selectedFiles[e.key] = null
      selectedFileNames[e.key] = ""
      loadingStates[e.key] = false
    })

    return {
      importEntities: IMPORT_ENTITIES,
      services: {
        settings: new SettingsImportService(),
        user_permissions: new UserPermissionImportService(),
        clusters: new ClusterImportService(),
        devices: new DeviceImportService(),
        customers: new CustomerImportService(),
        transactions: new TransactionImportService(),
        appliances: new ApplianceImportService(),
      },
      importStatusService: new ImportStatusService(),
      selectedFiles,
      selectedFileNames,
      loadingStates,
      importing: false,
      showResultDialog: false,
      resultDialogTitle: "",
      importResult: null,
      pollingJobId: null,
      pollingInterval: null,
      pendingResults: [],
      pollingEntityType: null,
    }
  },
  computed: {
    hasSelectedFiles() {
      return Object.values(this.selectedFiles).some((f) => f !== null)
    },
  },
  beforeDestroy() {
    this.stopPolling()
  },
  methods: {
    triggerFileInput(key) {
      const refs = this.$refs[`${key}-file-input`]
      const input = Array.isArray(refs) ? refs[0] : refs
      if (input) {
        input.click()
      }
    },
    handleFileSelect(event, key) {
      const file = event.target.files[0]
      if (file) {
        if (!file.name.endsWith(".json")) {
          this.alertNotify("error", "Please select a JSON file")
          return
        }
        this.$set(this.selectedFiles, key, file)
        this.$set(this.selectedFileNames, key, file.name)
      }
    },
    clearFile(key) {
      this.$set(this.selectedFiles, key, null)
      this.$set(this.selectedFileNames, key, "")
      const refs = this.$refs[`${key}-file-input`]
      const input = Array.isArray(refs) ? refs[0] : refs
      if (input) {
        input.value = ""
      }
    },
    clearAllFiles() {
      this.importEntities.forEach((e) => this.clearFile(e.key))
    },
    entityLabel(type) {
      const entity = IMPORT_ENTITIES.find((e) => e.key === type)
      return entity ? entity.label : type
    },
    async importAll() {
      if (!this.hasSelectedFiles) {
        this.alertNotify("warn", "Please select at least one file to import")
        return
      }

      this.$swal({
        type: "question",
        title: "Import Data",
        text: "Are you sure you want to import the selected files?",
        showCancelButton: true,
        confirmButtonText: "Yes, import",
        cancelButtonText: "Cancel",
      }).then(async (result) => {
        if ("value" in result) {
          this.importing = true
          const results = []

          for (const entity of this.importEntities) {
            if (!this.selectedFiles[entity.key]) {
              continue
            }

            try {
              this.$set(this.loadingStates, entity.key, true)
              const fileContent = await this.readFileAsText(
                this.selectedFiles[entity.key],
              )
              const jsonData = JSON.parse(fileContent)
              const data = jsonData.data || jsonData

              const importResult = await this.services[entity.key].import(data)

              if (importResult && importResult.async) {
                this.pendingResults = results
                this.pollingEntityType = entity.key
                this.startPolling(importResult.jobId)
                this.clearFile(entity.key)
                this.$set(this.loadingStates, entity.key, false)
                return
              }

              results.push({
                type: entity.key,
                success: true,
                data: importResult,
              })
              this.clearFile(entity.key)
            } catch (error) {
              const errorMessage =
                error.exception?.message ||
                error.message ||
                `Failed to import ${entity.label}`
              results.push({
                type: entity.key,
                success: false,
                error: errorMessage,
              })
            } finally {
              this.$set(this.loadingStates, entity.key, false)
            }
          }

          this.importing = false
          this.showResults(results)
        }
      })
    },
    startPolling(jobId) {
      this.pollingJobId = jobId
      this.pollingInterval = setInterval(async () => {
        try {
          const status = await this.importStatusService.getStatus(jobId)
          if (!status || status instanceof Error) {
            this.stopPolling()
            this.onAsyncImportDone(false, "Failed to check import status")
            return
          }

          if (status.status === "completed") {
            this.stopPolling()
            const importResult = status.result || {}
            this.onAsyncImportDone(importResult.success !== false, null, {
              imported_count: importResult.imported_count,
              failed_count: importResult.failed_count,
            })
          } else if (status.status === "failed") {
            this.stopPolling()
            this.onAsyncImportDone(
              false,
              status.error ||
                status.result?.errors?.transaction ||
                "Import failed",
            )
          }
        } catch (e) {
          this.stopPolling()
          this.onAsyncImportDone(false, "Failed to check import status")
        }
      }, POLL_INTERVAL_MS)
    },
    stopPolling() {
      if (this.pollingInterval) {
        clearInterval(this.pollingInterval)
        this.pollingInterval = null
      }
      this.pollingJobId = null
      this.importing = false
    },
    onAsyncImportDone(success, error, data) {
      const results = [...this.pendingResults]
      this.pendingResults = []
      const entityType = this.pollingEntityType
      this.pollingEntityType = null

      if (success) {
        results.push({ type: entityType, success: true, data })
      } else {
        results.push({
          type: entityType,
          success: false,
          error: error || "Import failed",
        })
      }

      this.showResults(results)
    },
    showResults(results) {
      const successCount = results.filter((r) => r.success).length
      const failCount = results.filter((r) => !r.success).length

      if (successCount > 0) {
        this.alertNotify(
          "success",
          `${successCount} import(s) completed successfully`,
        )
        EventBus.$emit("Settings")
      }
      if (failCount > 0) {
        this.alertNotify("error", `${failCount} import(s) failed`)
      }

      this.importResult = {
        results,
        success_count: successCount,
        failed_count: failCount,
      }
      this.resultDialogTitle = "Import Results"
      this.showResultDialog = true
    },
    readFileAsText(file) {
      return new Promise((resolve, reject) => {
        const reader = new FileReader()
        reader.onload = (e) => resolve(e.target.result)
        reader.onerror = () => reject(new Error("Failed to read file"))
        reader.readAsText(file)
      })
    },
  },
}
</script>

<style scoped lang="scss">
.md-table-cell {
  vertical-align: middle;
}

.entity-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.entity-icon {
  color: #448aff;
  margin: 0;
  display: flex;
  align-items: center;
}

.entity-description {
  color: #666;
  font-size: 0.875rem;
}

.action-cell {
  display: flex;
  align-items: center;
  justify-content: flex-end;
}

.file-selected {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem;
  background-color: #f5f5f5;
  border-radius: 4px;
}

.file-icon {
  color: #448aff;
}

.file-name {
  font-size: 0.875rem;
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.import-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e0e0e0;
}

.import-progress {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e0e0e0;
}

.import-progress-text {
  margin-top: 0.75rem;
  color: #666;
  font-size: 0.875rem;
  text-align: center;
}

.result-message {
  margin-bottom: 1rem;
}

.result-stats {
  margin: 1rem 0;
}

.result-stats p {
  margin: 0.5rem 0;
}

.result-details {
  margin-top: 1rem;
}

.result-details ul {
  margin: 0.5rem 0 0 1.5rem;
  padding: 0;
}

.result-details li {
  margin: 0.25rem 0;
}

.result-success {
  color: #2e7d32;
}

.result-failed {
  color: #c62828;
}
</style>
