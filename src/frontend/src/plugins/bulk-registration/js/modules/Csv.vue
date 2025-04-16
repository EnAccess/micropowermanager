<template>
  <div>
    <widget color="green" :title="title">
      <div class="md-layout md-gutter">
        <div
          class="md-layout-item md-size-30"
          style="
            margin: auto;
            text-align: center;
            padding: 3rem;
            font-size: initial;
            font-weight: 500;
            display: grid;
          "
        >
          <span>
            Please do not use this plugin to register your Spark & Stemaco meter
            records. These records will be synchronized automatically once you
            configure your credential settings for these plugins.
          </span>
          <span style="margin-top: 16px">
            You can download sample csv file from
            <a href="/files/bulk-registration-template.csv">here</a>
          </span>
        </div>
        <div class="md-layout-item md-size-70">
          <div :hidden="fileUploaded">
            <div v-if="!loading">
              <div class="upload-area">
                <input
                  type="file"
                  @change="uploadCsv"
                  accept=".csv"
                  ref="file-input"
                />
                <p>Drag your files here or click in this area.</p>
                <p v-text="fileName"></p>
                <p class="csv-p">Only .csv files.</p>
              </div>
              <div class="buttons-area" v-if="fileName !== ''">
                <md-button
                  role="button"
                  class="md-raised"
                  style="float: right"
                  @click="clear"
                >
                  clear
                </md-button>
                <md-button
                  role="button"
                  class="md-raised md-primary"
                  style="float: right"
                  :disabled="loading"
                  @click="upload"
                >
                  Upload File
                </md-button>
              </div>
            </div>
            <md-progress-bar md-mode="indeterminate" v-else />
          </div>
          <div :hidden="!fileUploaded">
            <div>
              <div style="text-align: left; padding-left: 5rem">
                <p style="font-weight: bold">Following records created.</p>

                <div
                  v-if="createdRecordCount === 0"
                  class="md-layout-item warning-message-area"
                >
                  Although file updated successful, there is no new record
                  created. Probably, records are already exists.
                </div>

                <div>
                  <span class="uploaded-wrap">
                    <label class="uploaded">Cluster :</label>
                    <span>
                      {{ csvUploadService.recentlyCreatedRecords.cluster }}
                    </span>
                  </span>
                  <span class="uploaded-wrap">
                    <label class="uploaded">Mini Grid :</label>
                    <span>
                      {{ csvUploadService.recentlyCreatedRecords.miniGrid }}
                    </span>
                  </span>
                  <span class="uploaded-wrap">
                    <label class="uploaded">Village :</label>
                    <span>
                      {{ csvUploadService.recentlyCreatedRecords.village }}
                    </span>
                  </span>
                  <span class="uploaded-wrap">
                    <label class="uploaded">Customer :</label>
                    <span>
                      {{ csvUploadService.recentlyCreatedRecords.customer }}
                    </span>
                  </span>
                  <span class="uploaded-wrap">
                    <label class="uploaded">Tariff :</label>
                    <span>
                      {{ csvUploadService.recentlyCreatedRecords.tariff }}
                    </span>
                  </span>
                  <span class="uploaded-wrap">
                    <label class="uploaded">Meter :</label>
                    <span>
                      {{ csvUploadService.recentlyCreatedRecords.meter }}
                    </span>
                  </span>
                  <span class="uploaded-wrap">
                    <label class="uploaded">Connection Type :</label>
                    <span>
                      {{
                        csvUploadService.recentlyCreatedRecords.connectionType
                      }}
                    </span>
                  </span>
                  <span class="uploaded-wrap">
                    <label class="uploaded">Connection Group :</label>
                    <span>
                      {{
                        csvUploadService.recentlyCreatedRecords.connectionGroup
                      }}
                    </span>
                  </span>
                </div>
              </div>
            </div>
            <div class="buttons-area">
              <md-button
                role="button"
                class="md-raised"
                style="float: right"
                @click="$router.push('/people')"
              >
                Done
              </md-button>
              <md-button
                role="button"
                class="md-raised md-primary"
                style="float: right"
                @click="uploadNewFile"
              >
                Upload New File
              </md-button>
            </div>
          </div>
        </div>
      </div>
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { CsvUploadService } from "../services/CsvUploadService"
import { notify } from "@/mixins/notify"

export default {
  name: "Csv",
  mixins: [notify],
  components: { Widget },
  data() {
    return {
      csvUploadService: new CsvUploadService(),
      csvFile: null,
      loading: false,
      title: "Customer Registration",
      fileName: "",
      fileUploaded: false,
      createdRecordCount: 0,
    }
  },

  methods: {
    uploadCsv(event) {
      event.preventDefault()
      const fileLocation =
        event.type === "change" ? "srcElement" : "dataTransfer"
      if (event[fileLocation].files.length !== 1) {
        let message = "Only one file is supported"
        this.alertNotify("warn", message)
        return
      }

      this.csvFile = event[fileLocation].files[0]
      this.fileName = this.$refs["file-input"].value
    },
    async upload() {
      if (!this.csvFile) {
        this.alertNotify("warn", "No file selected.")
        return
      }
      this.$swal({
        type: "question",
        title: this.title,
        text: "Are you sure to do this action?",
        showCancelButton: true,
        confirmButtonText: "I'm sure",
        cancelButtonText: "Cancel",
      }).then((result) => {
        if ("value" in result) {
          this.save()
        }
      })
    },
    async save() {
      this.loading = true
      try {
        await this.csvUploadService.create(this.csvFile)
        this.alertNotify("success", "Updated Successfully.")
        this.clear()
        this.createdRecordCountCheck()
        this.fileUploaded = true
      } catch (error) {
        this.$swal.fire(error.message)
      }

      this.loading = false
    },
    clear() {
      this.csvFile = null
      this.fileName = ""
    },
    uploadNewFile() {
      this.$refs["file-input"].value = null
      this.fileUploaded = false
    },
    createdRecordCountCheck() {
      for (let value of Object.values(
        this.csvUploadService.recentlyCreatedRecords,
      )) {
        this.createdRecordCount += value
      }
    },
  },
}
</script>

<style scoped>
.csv-p {
  font-size: x-small;
  font-weight: 500;
  color: gray;
}

.upload-area {
  margin: auto;
  width: 60%;
  padding: 10px;
  min-height: 4rem;
  border: 1px dashed;
  margin-bottom: 2rem;
  margin-top: 2rem;
}

.upload-area p {
  text-align: center;
  font-family: Arial;
}

.upload-area input {
  position: absolute;
  margin: 0;
  padding: 0;
  width: 100%;
  height: 100%;
  outline: none;
  opacity: 0;
}

.buttons-area {
  margin: auto;
  width: 60%;
  margin-bottom: 2rem;
  margin-top: 2rem;
}

.uploaded {
  display: inline-block;
  font-weight: 200;
  padding: 10px 5px;
  height: 37px;
  position: relative;
}

.uploaded-wrap {
  display: block;
  position: relative;
  /*box-shadow*/
  -webkit-box-shadow: 0 2px 0 -1px #ebebeb;
  -moz-box-shadow: 0 2px 0 -1px #ebebeb;
  box-shadow: 0 2px 0 -1px #ebebeb;
}

.uploaded-wrap:last-of-type {
  /*box-shadow*/
  -webkit-box-shadow: none;
  -moz-box-shadow: none;
  box-shadow: none;
}

.warning-message-area {
  padding: 20px;
  margin: 10px;
  d-webkit-border-radius: 16px;
  -moz-border-radius: 16px;
  border-radius: 16px;
  color: #856404;
  background-color: #fff3cd;
  border-color: #ffeeba;
}
</style>
