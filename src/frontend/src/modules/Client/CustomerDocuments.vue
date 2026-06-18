<template>
  <div>
    <widget
      :id="'client-documents'"
      :title="`${$tc('words.document', 2)}  ·  ${documents.length} / ${maxDocuments}`"
      :button="true"
      :button-text="$tc('phrases.uploadDocument')"
      color="primary"
      :button-icon="canUpload ? 'cloud_upload' : 'block'"
      @widgetAction="openUploadDialog"
    >
      <div v-if="documents.length === 0" class="documents-empty">
        <md-icon class="documents-empty__icon">description</md-icon>
        <p class="documents-empty__text">
          {{ $tc("phrases.noDocumentsYet") }}
        </p>
      </div>
      <md-table
        v-else
        style="width: 100%"
        v-model="documents"
        md-card
        md-fixed-header
      >
        <md-table-row slot="md-table-row" slot-scope="{ item }">
          <md-table-cell :md-label="$tc('words.name')">
            <div class="document-name-cell">
              <md-icon class="document-name-cell__icon">
                {{ documentIcon(item.original_name) }}
              </md-icon>
              <div class="document-name-cell__text">
                <span class="document-name-cell__title">
                  {{ item.original_name }}
                </span>
                <span class="document-name-cell__subtitle">
                  {{ item.type }}
                </span>
              </div>
            </div>
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.size')">
            {{ formatSize(item.file_size) }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.actions')">
            <md-button
              class="md-icon-button md-dense"
              @click="openEditDialog(item)"
            >
              <md-tooltip md-direction="top">
                {{ $tc("phrases.additionalInfo") }}
              </md-tooltip>
              <md-icon>edit_note</md-icon>
            </md-button>
            <md-button
              class="md-icon-button md-dense"
              :disabled="downloadingId === item.id"
              @click="download(item)"
            >
              <md-tooltip md-direction="top">
                {{ $tc("words.download") }}
              </md-tooltip>
              <md-icon>download</md-icon>
            </md-button>
            <md-button
              class="md-icon-button md-dense md-accent"
              @click="confirmDelete(item)"
            >
              <md-tooltip md-direction="top">
                {{ $tc("words.delete") }}
              </md-tooltip>
              <md-icon>delete_outline</md-icon>
            </md-button>
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>

    <md-dialog
      class="document-upload-dialog"
      :md-active.sync="dialogVisible"
      :md-click-outside-to-close="false"
    >
      <header class="dialog-header">
        <div class="dialog-header__icon">
          <md-icon>cloud_upload</md-icon>
        </div>
        <div class="dialog-header__text">
          <h2 class="dialog-header__title">
            {{ $tc("phrases.uploadDocument") }}
          </h2>
          <p class="dialog-header__subtitle">
            {{ $tc("phrases.uploadDocumentHint") }}
          </p>
        </div>
      </header>

      <md-dialog-content class="md-scrollbar dialog-content">
        <section class="form-section">
          <label class="form-section__label" for="document-type">
            {{ $tc("phrases.documentType") }}
          </label>
          <md-field class="form-section__field" md-clearable>
            <md-input
              id="document-type"
              v-model="newDocument.type"
              :placeholder="$tc('phrases.documentTypePlaceholder')"
            />
          </md-field>
        </section>

        <section class="form-section">
          <label class="form-section__label">
            {{ $tc("words.file") }}
          </label>

          <div
            v-if="!newDocument.file"
            class="dropzone"
            :class="{ 'dropzone--active': isDragging }"
            @click="triggerFileInput"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="onFileDropped"
          >
            <md-icon class="dropzone__icon">file_upload</md-icon>
            <p class="dropzone__title">
              {{ $tc("phrases.dropFileHere") }}
            </p>
            <p class="dropzone__subtitle">
              {{ $tc("phrases.fileConstraintsHint") }}
            </p>
            <input
              ref="fileInput"
              type="file"
              accept=".pdf,.docx"
              class="dropzone__input"
              @change="onFileSelected"
            />
          </div>

          <div v-else class="file-chip">
            <md-icon class="file-chip__icon">
              {{ documentIcon(newDocument.file.name) }}
            </md-icon>
            <div class="file-chip__text">
              <span class="file-chip__name">{{ newDocument.file.name }}</span>
              <span class="file-chip__size">
                {{ formatSize(newDocument.file.size) }}
              </span>
            </div>
            <md-button class="md-icon-button md-dense" @click="clearFile">
              <md-icon>close</md-icon>
            </md-button>
          </div>
        </section>

        <section class="form-section form-section--additional">
          <header class="form-section__header">
            <div>
              <span class="form-section__label">
                {{ $tc("phrases.additionalInfo") }}
              </span>
              <p class="form-section__hint">
                {{ $tc("phrases.additionalInfoHint") }}
              </p>
            </div>
            <md-button
              class="md-icon-button md-dense md-primary md-raised"
              @click="addExtraField"
            >
              <md-tooltip md-direction="top">
                {{ $tc("phrases.addField") }}
              </md-tooltip>
              <md-icon>add</md-icon>
            </md-button>
          </header>

          <div
            v-if="extraFields.length === 0"
            class="extra-fields-empty"
            @click="addExtraField"
          >
            <md-icon>note_add</md-icon>
            <span>{{ $tc("phrases.addFirstField") }}</span>
          </div>

          <div v-else class="extra-fields">
            <div
              v-for="(field, index) in extraFields"
              :key="index"
              class="extra-field-row"
            >
              <md-field class="extra-field-row__field">
                <label>{{ $tc("words.key") }}</label>
                <md-input v-model="field.key" />
              </md-field>
              <md-field class="extra-field-row__field">
                <label>{{ $tc("words.value") }}</label>
                <md-input v-model="field.value" />
              </md-field>
              <md-button
                class="md-icon-button md-dense extra-field-row__remove"
                @click="removeExtraField(index)"
              >
                <md-icon>close</md-icon>
              </md-button>
            </div>
          </div>
        </section>
      </md-dialog-content>

      <md-dialog-actions class="dialog-actions">
        <md-button @click="closeDialog">
          {{ $tc("words.close") }}
        </md-button>
        <md-button
          class="md-primary md-raised"
          :disabled="!canSubmit"
          @click="submitUpload"
        >
          <md-icon>cloud_upload</md-icon>
          <span class="dialog-actions__label">
            {{ $tc("phrases.upload") }}
          </span>
        </md-button>
      </md-dialog-actions>
    </md-dialog>

    <md-dialog
      class="document-info-dialog"
      :md-active.sync="editDialogVisible"
      :md-click-outside-to-close="false"
    >
      <header class="dialog-header">
        <div class="dialog-header__icon">
          <md-icon>edit_note</md-icon>
        </div>
        <div class="dialog-header__text">
          <h2 class="dialog-header__title">
            {{ $tc("phrases.additionalInfo") }}
          </h2>
          <p class="dialog-header__subtitle">
            {{ editDocumentName }}
          </p>
        </div>
      </header>
      <md-dialog-content class="md-scrollbar dialog-content">
        <section class="form-section form-section--additional">
          <header class="form-section__header">
            <p class="form-section__hint">
              {{ $tc("phrases.additionalInfoHint") }}
            </p>
            <md-button
              class="md-icon-button md-dense md-primary md-raised"
              @click="addEditField"
            >
              <md-tooltip md-direction="top">
                {{ $tc("phrases.addField") }}
              </md-tooltip>
              <md-icon>add</md-icon>
            </md-button>
          </header>

          <div
            v-if="editFields.length === 0"
            class="extra-fields-empty"
            @click="addEditField"
          >
            <md-icon>note_add</md-icon>
            <span>{{ $tc("phrases.addFirstField") }}</span>
          </div>

          <div v-else class="extra-fields">
            <div
              v-for="(field, index) in editFields"
              :key="index"
              class="extra-field-row"
            >
              <md-field class="extra-field-row__field">
                <label>{{ $tc("words.key") }}</label>
                <md-input v-model="field.key" />
              </md-field>
              <md-field class="extra-field-row__field">
                <label>{{ $tc("words.value") }}</label>
                <md-input v-model="field.value" />
              </md-field>
              <md-button
                class="md-icon-button md-dense extra-field-row__remove"
                @click="removeEditField(index)"
              >
                <md-icon>close</md-icon>
              </md-button>
            </div>
          </div>
        </section>
      </md-dialog-content>
      <md-dialog-actions class="dialog-actions">
        <md-button @click="editDialogVisible = false">
          {{ $tc("words.close") }}
        </md-button>
        <md-button
          class="md-primary md-raised"
          :disabled="savingEdit"
          @click="saveAdditionalInfo"
        >
          <md-icon>save</md-icon>
          <span class="dialog-actions__label">
            {{ $tc("words.save") }}
          </span>
        </md-button>
      </md-dialog-actions>
    </md-dialog>
  </div>
</template>

<script>
import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import { notify } from "@/mixins/notify.js"
import { PersonService } from "@/services/PersonService.js"
import Widget from "@/shared/Widget.vue"

const MAX_DOCUMENTS = 3
const MAX_BYTES = 5 * 1024 * 1024
const ALLOWED_EXTENSIONS = ["pdf", "docx"]

export default {
  name: "CustomerDocuments",
  components: { Widget },
  mixins: [notify],
  props: {
    personId: {
      type: Number,
      required: true,
    },
  },
  data() {
    return {
      personService: new PersonService(),
      documents: [],
      dialogVisible: false,
      newDocument: {
        type: "",
        file: null,
      },
      extraFields: [],
      maxDocuments: MAX_DOCUMENTS,
      isDragging: false,
      downloadingId: null,
      editDialogVisible: false,
      editDocument: null,
      editFields: [],
      savingEdit: false,
    }
  },
  computed: {
    canUpload() {
      return this.documents.length < this.maxDocuments
    },
    canSubmit() {
      return (
        this.canUpload &&
        this.newDocument.file !== null &&
        this.newDocument.type.trim().length > 0
      )
    },
    editDocumentName() {
      return this.editDocument?.original_name ?? ""
    },
  },
  mounted() {
    this.loadDocuments()
  },
  methods: {
    async loadDocuments() {
      const response = await this.personService.listDocuments(this.personId)
      if (response instanceof ErrorHandler) {
        this.alertNotify("error", response.errorMessage)
        return
      }
      this.documents = response ?? []
    },
    openUploadDialog() {
      if (!this.canUpload) {
        this.alertNotify("warn", this.$tc("phrases.maxDocumentsReached"))
        return
      }
      this.dialogVisible = true
    },
    closeDialog() {
      this.dialogVisible = false
      this.newDocument = { type: "", file: null }
      this.extraFields = []
      this.isDragging = false
      if (this.$refs.fileInput) {
        this.$refs.fileInput.value = ""
      }
    },
    triggerFileInput() {
      this.$refs.fileInput?.click()
    },
    addExtraField() {
      this.extraFields.push({ key: "", value: "" })
    },
    removeExtraField(index) {
      this.extraFields.splice(index, 1)
    },
    clearFile() {
      this.newDocument.file = null
      if (this.$refs.fileInput) {
        this.$refs.fileInput.value = ""
      }
    },
    onFileSelected(event) {
      this.applyFile(event.target.files[0])
    },
    onFileDropped(event) {
      this.isDragging = false
      const file = event.dataTransfer?.files?.[0]
      this.applyFile(file)
    },
    applyFile(file) {
      if (!file) {
        this.newDocument.file = null
        return
      }
      if (file.size > MAX_BYTES) {
        this.alertNotify("error", this.$tc("phrases.fileTooLarge"))
        this.clearFile()
        return
      }
      const extension = file.name.split(".").pop().toLowerCase()
      if (!ALLOWED_EXTENSIONS.includes(extension)) {
        this.alertNotify("error", this.$tc("phrases.unsupportedFileType"))
        this.clearFile()
        return
      }
      this.newDocument.file = file
    },
    async submitUpload() {
      if (!this.canSubmit) {
        return
      }
      const additional = {}
      this.extraFields.forEach((field) => {
        const key = field.key.trim()
        if (key.length > 0) {
          additional[key] = field.value
        }
      })
      const response = await this.personService.uploadDocument(
        this.personId,
        this.newDocument.file,
        this.newDocument.type.trim(),
        additional,
      )
      if (response instanceof ErrorHandler) {
        this.alertNotify("error", response.errorMessage)
        return
      }
      this.documents.push(response)
      this.alertNotify("success", this.$tc("phrases.documentUploaded"))
      this.closeDialog()
    },
    confirmDelete(document) {
      this.$swal({
        type: "warning",
        title: this.$tc("phrases.deleteDocument"),
        text: document.original_name,
        showCancelButton: true,
      }).then(async (result) => {
        if (!result.value) return
        const response = await this.personService.deleteDocument(document.id)
        if (response instanceof ErrorHandler) {
          this.alertNotify("error", response.errorMessage)
          return
        }
        this.documents = this.documents.filter((d) => d.id !== document.id)
        this.alertNotify("success", this.$tc("phrases.documentDeleted"))
      })
    },
    formatSize(bytes) {
      if (!bytes) return "—"
      const kb = bytes / 1024
      if (kb < 1024) return `${kb.toFixed(1)} KB`
      return `${(kb / 1024).toFixed(2)} MB`
    },
    documentIcon(filename) {
      const ext = (filename ?? "").split(".").pop().toLowerCase()
      if (ext === "pdf") return "picture_as_pdf"
      if (ext === "docx") return "article"
      return "insert_drive_file"
    },
    openEditDialog(document) {
      this.editDocument = document
      const info = document?.additional_json ?? {}
      this.editFields = Object.entries(info).map(([key, value]) => ({
        key,
        value: value === null ? "" : String(value),
      }))
      this.editDialogVisible = true
    },
    addEditField() {
      this.editFields.push({ key: "", value: "" })
    },
    removeEditField(index) {
      this.editFields.splice(index, 1)
    },
    async saveAdditionalInfo() {
      const additional = {}
      this.editFields.forEach((field) => {
        const key = field.key.trim()
        if (key.length > 0) {
          additional[key] = field.value
        }
      })
      this.savingEdit = true
      const response = await this.personService.updateDocumentAdditional(
        this.editDocument.id,
        additional,
      )
      this.savingEdit = false
      if (response instanceof ErrorHandler) {
        this.alertNotify("error", response.errorMessage)
        return
      }
      const index = this.documents.findIndex((d) => d.id === response.id)
      if (index !== -1) {
        this.$set(this.documents, index, response)
      }
      this.alertNotify("success", this.$tc("phrases.additionalInfoUpdated"))
      this.editDialogVisible = false
    },
    async download(document) {
      this.downloadingId = document.id
      const result = await this.personService.downloadDocument(
        document.id,
        document.original_name,
      )
      this.downloadingId = null
      if (result instanceof ErrorHandler) {
        this.alertNotify("error", result.errorMessage)
      }
    },
  },
}
</script>

<style lang="scss" scoped>
$primary: #2c5f88;
$primary-light: rgba(44, 95, 136, 0.08);
$border-subtle: rgba(0, 0, 0, 0.08);
$text-muted: rgba(0, 0, 0, 0.55);

.documents-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2.5rem 1rem;
  color: $text-muted;

  &__icon {
    font-size: 3rem !important;
    color: rgba(0, 0, 0, 0.25) !important;
    margin-bottom: 0.5rem;
  }

  &__text {
    margin: 0;
    font-size: 0.95rem;
  }
}

.document-name-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;

  &__icon {
    color: $primary !important;
  }

  &__text {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
  }

  &__title {
    font-weight: 500;
    color: rgba(0, 0, 0, 0.85);
  }

  &__subtitle {
    font-size: 0.75rem;
    color: $text-muted;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }
}

::v-deep .document-upload-dialog {
  .md-dialog-container {
    width: 540px;
    max-width: 92vw;
    border-radius: 12px;
    overflow: hidden;
  }
}

.dialog-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem 1.5rem 1rem;
  border-bottom: 1px solid $border-subtle;

  &__icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: $primary-light;
    display: flex;
    align-items: center;
    justify-content: center;

    .md-icon {
      color: $primary !important;
      font-size: 24px !important;
    }
  }

  &__title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 600;
    color: rgba(0, 0, 0, 0.87);
  }

  &__subtitle {
    margin: 0.15rem 0 0;
    font-size: 0.85rem;
    color: $text-muted;
  }
}

.dialog-content {
  padding: 1.25rem 1.5rem;
  max-height: 70vh;
}

.form-section {
  margin-bottom: 1.5rem;

  &:last-child {
    margin-bottom: 0;
  }

  &__label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: rgba(0, 0, 0, 0.7);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
  }

  &__hint {
    margin: 0.15rem 0 0;
    font-size: 0.78rem;
    color: $text-muted;
  }

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    gap: 0.5rem;
  }

  &__field {
    margin: 0;
  }
}

.dropzone {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 2rem 1rem;
  border: 2px dashed rgba(44, 95, 136, 0.35);
  border-radius: 10px;
  background: $primary-light;
  cursor: pointer;
  transition:
    background 0.2s ease,
    border-color 0.2s ease;

  &:hover,
  &--active {
    background: rgba(44, 95, 136, 0.14);
    border-color: $primary;
  }

  &__icon {
    font-size: 2.5rem !important;
    color: $primary !important;
    margin-bottom: 0.5rem;
  }

  &__title {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 500;
    color: rgba(0, 0, 0, 0.8);
  }

  &__subtitle {
    margin: 0.35rem 0 0;
    font-size: 0.78rem;
    color: $text-muted;
  }

  &__input {
    position: absolute;
    inset: 0;
    opacity: 0;
    pointer-events: none;
  }
}

.file-chip {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border: 1px solid $border-subtle;
  border-radius: 10px;
  background: #fff;

  &__icon {
    color: $primary !important;
    font-size: 28px !important;
  }

  &__text {
    flex: 1;
    display: flex;
    flex-direction: column;
    line-height: 1.2;
    min-width: 0;
  }

  &__name {
    font-weight: 500;
    color: rgba(0, 0, 0, 0.85);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  &__size {
    font-size: 0.75rem;
    color: $text-muted;
  }
}

.extra-fields-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 1rem;
  border: 1px dashed $border-subtle;
  border-radius: 10px;
  color: $text-muted;
  font-size: 0.85rem;
  cursor: pointer;
  transition: background 0.2s ease;

  &:hover {
    background: rgba(0, 0, 0, 0.02);
  }

  .md-icon {
    color: $text-muted !important;
  }
}

.extra-fields {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.extra-field-row {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 0.5rem;
  align-items: center;

  &__field {
    margin: 0;
  }

  &__remove {
    color: $text-muted !important;
  }
}

.dialog-actions {
  padding: 0.75rem 1.5rem 1rem;
  border-top: 1px solid $border-subtle;
  gap: 0.5rem;

  &__label {
    margin-left: 0.35rem;
  }
}

::v-deep .document-info-dialog {
  .md-dialog-container {
    width: 460px;
    max-width: 92vw;
    border-radius: 12px;
    overflow: hidden;
  }
}
</style>
