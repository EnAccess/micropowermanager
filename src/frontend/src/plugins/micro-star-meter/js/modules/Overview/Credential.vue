<template>
  <div class="md-layout md-gutter">
    <form
      @submit.prevent="submitCredentialForm"
      data-vv-scope="Credential-Form"
      class="Credential-Form md-layout-item md-xlarge-size-50 md-large-size-50 md-medium-size-50 md-small-size-100"
    >
      <md-card>
        <md-card-content style="min-height: 80%">
          <div class="md-layout-item md-size-100">
            <div
              class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-100"
            >
              <div
                class="md-layout-item md-xlarge-size-50 md-large-size-50 md-medium-size-50 md-small-size-100"
              >
                <md-field
                  :class="{
                    'md-invalid': errors.has('Credential-Form.apiUrl'),
                  }"
                >
                  <label for="username">API URL</label>
                  <md-input
                    id="apiUrl"
                    name="apiUrl"
                    v-model="credentialService.credential.apiUrl"
                    v-validate="'required|min:3'"
                  />
                  <span class="md-error">
                    {{ errors.first("Credential-Form.apiUrl") }}
                  </span>
                </md-field>
              </div>
              <div
                class="md-layout-item md-xlarge-size-50 md-large-size-50 md-medium-size-50 md-small-size-100"
              >
                <md-field
                  :class="{
                    'md-invalid': errors.has(
                      'Credential-Form.certificatePassword',
                    ),
                  }"
                >
                  <label for="certificatePassword">Certificate Password</label>
                  <md-input
                    id="certificatePassword"
                    name="certificatePassword"
                    v-model="credentialService.credential.certificatePassword"
                    v-validate="'min:3'"
                    type="password"
                  />
                  <span class="md-error">
                    {{ errors.first("Credential-Form.certificatePassword") }}
                  </span>
                </md-field>
              </div>
            </div>
          </div>
        </md-card-content>
        <md-progress-bar md-mode="indeterminate" v-if="loading" />
        <md-card-actions>
          <md-button class="md-raised md-primary" type="submit">Save</md-button>
        </md-card-actions>
      </md-card>
    </form>

    <div
      class="md-layout-item md-xlarge-size-50 md-large-size-50 md-medium-size-50 md-small-size-100"
    >
      <md-card>
        <md-card-content>
          <div class="md-layout md-gutter">
            <div class="md-layout-item md-size-100">
              <span>Please upload your certificate file.</span>
              <div v-if="!loading">
                <div class="upload-area">
                  <input
                    type="file"
                    @change="uploadCert"
                    accept=".p12"
                    ref="file-input"
                  />
                  <p>Drag your files here or click in this area.</p>
                  <p v-text="fileName"></p>
                  <p class="cert-p">Only .p12 files.</p>
                </div>
              </div>
            </div>
          </div>
        </md-card-content>
        <md-card-actions>
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
            :disabled="certLoading"
            @click="upload()"
          >
            Upload File
          </md-button>
        </md-card-actions>
        <md-progress-bar md-mode="indeterminate" v-if="certLoading" />
      </md-card>
    </div>
  </div>
</template>

<script>
import { CredentialService } from "../../services/CredentialService"
import { CertService } from "../../services/CertService"
import { EventBus } from "@/shared/eventbus"
import { notify } from "@/mixins/notify"

export default {
  name: "Credential",
  mixins: [notify],
  data() {
    return {
      credentialService: new CredentialService(),
      certService: new CertService(),
      loading: false,
      fileName: "",
      fileUploaded: false,
      certFile: null,
      certLoading: false,
    }
  },
  mounted() {
    this.getCredential()
    this.getUploadedCert()
  },
  methods: {
    async getUploadedCert() {
      this.fileName = await this.certService.getUploadedCertName()
    },
    async getCredential() {
      await this.credentialService.getCredential()
    },
    async submitCredentialForm() {
      let validator = await this.$validator.validateAll("Credential-Form")
      if (!validator) {
        return
      }
      try {
        this.loading = true
        await this.credentialService.updateCredential()
        this.alertNotify("success", "Authentication Successful")
        EventBus.$emit("MicroStar Meter")
      } catch (e) {
        this.alertNotify("error", e.message)
      }
      this.loading = false
    },
    async upload() {
      if (!this.certFile) {
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
      this.certLoading = true
      try {
        await this.certService.upload(this.certFile)
        this.alertNotify("success", "Updated Successfully.")
        this.clear()
        this.fileUploaded = true
      } catch (error) {
        this.$swal.fire(error.message)
      }

      this.certLoading = false
    },
    clear() {
      this.certFile = null
      this.fileName = ""
    },
    uploadCert(event) {
      event.preventDefault()
      const fileLocation =
        event.type === "change" ? "srcElement" : "dataTransfer"
      if (event[fileLocation].files.length !== 1) {
        let message = "Only one file is supported"
        this.alertNotify("warn", message)
        return
      }
      const nameOfFile = event[fileLocation].files[0].name
      if (nameOfFile.slice(nameOfFile.length - 4) !== ".p12") {
        let message = "Only .p12 files are supported"
        this.alertNotify("warn", message)
        return
      }

      this.certFile = event[fileLocation].files[0]
      this.fileName = this.$refs["file-input"].value
    },
  },
}
</script>

<style lang="scss" scoped>
.md-card {
  height: 100% !important;
}

.Credential-Form {
  height: 100% !important;
}

.cert-p {
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
