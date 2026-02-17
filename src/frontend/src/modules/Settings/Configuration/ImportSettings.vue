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

          <md-table-row>
            <md-table-cell>
              <div class="entity-info">
                <md-icon class="entity-icon">settings</md-icon>
                <span>Settings</span>
              </div>
            </md-table-cell>
            <md-table-cell>
              <span class="entity-description">
                Import system settings from exported JSON file
              </span>
            </md-table-cell>
            <md-table-cell>
              <div class="action-cell">
                <input
                  type="file"
                  @change="handleSettingsFileSelect"
                  accept=".json"
                  ref="settings-file-input"
                  style="display: none"
                />
                <md-button
                  v-if="!selectedSettingsFile && !settingsLoading"
                  class="md-primary md-raised md-dense"
                  @click="$refs['settings-file-input'].click()"
                >
                  <md-icon>file_upload</md-icon>
                  {{ $tc("words.select") }} {{ $tc("words.file") }}
                </md-button>
                <div
                  v-else-if="selectedSettingsFile && !settingsLoading"
                  class="file-selected"
                >
                  <md-icon class="file-icon">insert_drive_file</md-icon>
                  <span class="file-name">{{ selectedSettingsFileName }}</span>
                  <md-button
                    class="md-icon-button md-dense"
                    @click="clearSettingsFile"
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

          <md-table-row>
            <md-table-cell>
              <div class="entity-info">
                <md-icon class="entity-icon">people</md-icon>
                <span>User Permissions</span>
              </div>
            </md-table-cell>
            <md-table-cell>
              <span class="entity-description">
                Import user permissions and roles from exported JSON file
              </span>
            </md-table-cell>
            <md-table-cell>
              <div class="action-cell">
                <input
                  type="file"
                  @change="handleUserPermissionFileSelect"
                  accept=".json"
                  ref="user-permission-file-input"
                  style="display: none"
                />
                <md-button
                  v-if="!selectedUserPermissionFile && !userPermissionLoading"
                  class="md-primary md-raised md-dense"
                  @click="$refs['user-permission-file-input'].click()"
                >
                  <md-icon>file_upload</md-icon>
                  {{ $tc("words.select") }} {{ $tc("words.file") }}
                </md-button>
                <div
                  v-else-if="
                    selectedUserPermissionFile && !userPermissionLoading
                  "
                  class="file-selected"
                >
                  <md-icon class="file-icon">insert_drive_file</md-icon>
                  <span class="file-name">
                    {{ selectedUserPermissionFileName }}
                  </span>
                  <md-button
                    class="md-icon-button md-dense"
                    @click="clearUserPermissionFile"
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

        <div class="import-actions" v-if="hasSelectedFiles">
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
                  {{ r.type === "settings" ? "Settings" : "User permissions" }}:
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
import { SettingsImportService } from "@/services/SettingsImportService"
import { UserPermissionImportService } from "@/services/UserPermissionImportService"
import { notify } from "@/mixins/notify"
import { EventBus } from "@/shared/eventbus"

export default {
  name: "ImportSettings",
  mixins: [notify],
  data() {
    return {
      settingsImportService: new SettingsImportService(),
      userPermissionImportService: new UserPermissionImportService(),
      selectedSettingsFile: null,
      selectedSettingsFileName: "",
      selectedUserPermissionFile: null,
      selectedUserPermissionFileName: "",
      settingsLoading: false,
      userPermissionLoading: false,
      importing: false,
      showResultDialog: false,
      resultDialogTitle: "",
      importResult: null,
    }
  },
  computed: {
    hasSelectedFiles() {
      return this.selectedSettingsFile || this.selectedUserPermissionFile
    },
  },
  methods: {
    handleSettingsFileSelect(event) {
      const file = event.target.files[0]
      if (file) {
        if (!file.name.endsWith(".json")) {
          this.alertNotify("error", "Please select a JSON file")
          return
        }
        this.selectedSettingsFile = file
        this.selectedSettingsFileName = file.name
      }
    },
    handleUserPermissionFileSelect(event) {
      const file = event.target.files[0]
      if (file) {
        if (!file.name.endsWith(".json")) {
          this.alertNotify("error", "Please select a JSON file")
          return
        }
        this.selectedUserPermissionFile = file
        this.selectedUserPermissionFileName = file.name
      }
    },
    clearSettingsFile() {
      this.selectedSettingsFile = null
      this.selectedSettingsFileName = ""
      if (this.$refs["settings-file-input"]) {
        this.$refs["settings-file-input"].value = ""
      }
    },
    clearUserPermissionFile() {
      this.selectedUserPermissionFile = null
      this.selectedUserPermissionFileName = ""
      if (this.$refs["user-permission-file-input"]) {
        this.$refs["user-permission-file-input"].value = ""
      }
    },
    clearAllFiles() {
      this.clearSettingsFile()
      this.clearUserPermissionFile()
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

          if (this.selectedSettingsFile) {
            try {
              this.settingsLoading = true
              const fileContent = await this.readFileAsText(
                this.selectedSettingsFile,
              )
              const jsonData = JSON.parse(fileContent)
              const data = jsonData.data || jsonData
              const result = await this.settingsImportService.import(data)
              results.push({ type: "settings", success: true, data: result })
              this.clearSettingsFile()
            } catch (error) {
              const errorMessage =
                error.exception?.message ||
                error.message ||
                "Failed to import settings"
              results.push({
                type: "settings",
                success: false,
                error: errorMessage,
              })
            } finally {
              this.settingsLoading = false
            }
          }

          if (this.selectedUserPermissionFile) {
            try {
              this.userPermissionLoading = true
              const fileContent = await this.readFileAsText(
                this.selectedUserPermissionFile,
              )
              const jsonData = JSON.parse(fileContent)
              const data = jsonData.data || jsonData
              const result = await this.userPermissionImportService.import(data)
              results.push({
                type: "user_permissions",
                success: true,
                data: result,
              })
              this.clearUserPermissionFile()
            } catch (error) {
              const errorMessage =
                error.exception?.message ||
                error.message ||
                "Failed to import user permissions"
              results.push({
                type: "user_permissions",
                success: false,
                error: errorMessage,
              })
            } finally {
              this.userPermissionLoading = false
            }
          }

          this.importing = false

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
        }
      })
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

<style scoped>
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
