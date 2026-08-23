<template>
  <div>
    <widget
      :id="'client-onboarding'"
      :title="widgetTitle"
      :button="true"
      :button-text="$tc('phrases.addQuestion')"
      color="primary"
      :button-icon="canAdd ? 'playlist_add' : 'block'"
      @widgetAction="openAddDialog"
    >
      <div v-if="questions.length === 0" class="onboarding-empty">
        <md-icon class="onboarding-empty__icon">assignment</md-icon>
        <p class="onboarding-empty__text">
          {{ $tc("phrases.noOnboardingAnswersYet") }}
        </p>
      </div>

      <md-table
        v-else
        :key="tableKey"
        v-model="pagedRows"
        style="width: 100%"
        md-card
      >
        <md-table-row slot="md-table-row" slot-scope="{ item }">
          <md-table-cell :md-label="$tc('words.question')">
            <span
              class="answer-cell answer-cell--question"
              @click="openEditDialog(item.question)"
            >
              {{ item.question }}
            </span>
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.answer')">
            <span class="answer-cell" @click="openEditDialog(item.question)">
              {{ displayAnswer(item.answer) }}
            </span>
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.actions')">
            <md-button
              class="md-icon-button md-dense"
              @click="openEditDialog(item.question)"
            >
              <md-tooltip md-direction="top">
                {{ $tc("phrases.editQuestion") }}
              </md-tooltip>
              <md-icon>edit_note</md-icon>
            </md-button>
            <md-button
              class="md-icon-button md-dense md-accent"
              @click="confirmRemove(item.question)"
            >
              <md-tooltip md-direction="top">
                {{ $tc("words.delete") }}
              </md-tooltip>
              <md-icon>delete_outline</md-icon>
            </md-button>
          </md-table-cell>
        </md-table-row>

        <md-table-pagination
          :md-data="rows"
          :md-paginated-data.sync="pagedRows"
          :md-page-size="perPage"
          :md-page-options="[10, 25, 50]"
        />
      </md-table>
    </widget>

    <md-dialog
      class="onboarding-dialog"
      :md-active.sync="dialogVisible"
      :md-click-outside-to-close="false"
    >
      <header class="dialog-header">
        <div class="dialog-header__icon">
          <md-icon>assignment</md-icon>
        </div>
        <div class="dialog-header__text">
          <h2 class="dialog-header__title">
            {{ dialogTitle }}
          </h2>
          <p class="dialog-header__subtitle">
            {{ $tc("phrases.onboardingHint") }}
          </p>
        </div>
      </header>

      <md-dialog-content class="md-scrollbar dialog-content">
        <section class="form-section">
          <label class="form-section__label" for="onboarding-question">
            {{ $tc("words.question") }}
          </label>
          <md-field
            class="form-section__field"
            :class="{ 'md-invalid': isDuplicate }"
          >
            <md-input
              id="onboarding-question"
              v-model="form.question"
              maxlength="255"
            />
            <span v-if="isDuplicate" class="md-error">
              {{ $tc("phrases.duplicateQuestion") }}
            </span>
          </md-field>
        </section>

        <section class="form-section">
          <label class="form-section__label" for="onboarding-answer">
            {{ $tc("words.answer") }}
          </label>
          <md-field class="form-section__field">
            <md-textarea
              id="onboarding-answer"
              v-model="form.answer"
              maxlength="1000"
              md-autogrow
            />
          </md-field>
        </section>
      </md-dialog-content>

      <md-dialog-actions class="dialog-actions">
        <md-button @click="dialogVisible = false">
          {{ $tc("words.close") }}
        </md-button>
        <md-button
          class="md-primary md-raised"
          :disabled="!canSave"
          @click="save"
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

const MAX_ANSWERS = 50
const ANSWERS_PER_PAGE = 10

export default {
  name: "CustomerOnboarding",
  components: { Widget },
  mixins: [notify],
  props: {
    personId: {
      type: Number,
      required: true,
    },
    initialAnswers: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      personService: new PersonService(),
      answers: { ...this.initialAnswers },
      dialogVisible: false,
      saving: false,
      // The question being edited, null while adding a new one.
      editedQuestion: null,
      form: {
        question: "",
        answer: "",
      },
      maxAnswers: MAX_ANSWERS,
      perPage: ANSWERS_PER_PAGE,
      // md-table-pagination writes the current page's slice back into this.
      pagedRows: [],
      tableKey: 0,
    }
  },
  computed: {
    questions() {
      return Object.keys(this.answers)
    },
    rows() {
      return this.questions.map((question) => ({
        question,
        answer: this.answers[question],
      }))
    },
    widgetTitle() {
      if (this.questions.length === 0) {
        return this.$tc("words.onboarding")
      }
      return `${this.$tc("words.onboarding")}  ·  ${this.questions.length}`
    },
    canAdd() {
      return this.questions.length < this.maxAnswers
    },
    dialogTitle() {
      return this.editedQuestion === null
        ? this.$tc("phrases.addQuestion")
        : this.$tc("phrases.editQuestion")
    },
    isDuplicate() {
      const question = this.form.question.trim().toLowerCase()
      if (question.length === 0) {
        return false
      }
      return this.questions.some(
        (existing) =>
          existing !== this.editedQuestion &&
          existing.trim().toLowerCase() === question,
      )
    },
    canSave() {
      return (
        !this.saving &&
        this.form.question.trim().length > 0 &&
        !this.isDuplicate
      )
    },
  },
  watch: {
    initialAnswers(answers) {
      this.answers = { ...answers }
    },
  },
  methods: {
    displayAnswer(answer) {
      const text = answer === null ? "" : String(answer).trim()

      return text.length > 0 ? text : "—"
    },
    openAddDialog() {
      if (!this.canAdd) {
        this.alertNotify(
          "warn",
          this.$tc("phrases.maxOnboardingAnswersReached"),
        )
        return
      }
      this.editedQuestion = null
      this.form = { question: "", answer: "" }
      this.dialogVisible = true
    },
    openEditDialog(question) {
      const answer = this.answers[question]
      this.editedQuestion = question
      this.form = {
        question,
        answer: answer === null ? "" : String(answer),
      }
      this.dialogVisible = true
    },
    async save() {
      const question = this.form.question.trim()
      const rows = []
      let replaced = false

      // Rebuilding from the current map keeps the existing question order, so an
      // edited question stays where it was even when its text changes.
      this.questions.forEach((existing) => {
        if (existing === this.editedQuestion) {
          rows.push({ question, answer: this.form.answer })
          replaced = true
          return
        }
        rows.push({ question: existing, answer: this.answers[existing] })
      })

      if (!replaced) {
        rows.unshift({ question, answer: this.form.answer })
      }

      const persisted = await this.persist(rows)
      if (!persisted) {
        return
      }

      if (!replaced) {
        ++this.tableKey
      }

      this.alertNotify("success", this.$tc("phrases.onboardingUpdated"))
      this.dialogVisible = false
    },
    confirmRemove(question) {
      this.$swal({
        type: "warning",
        title: this.$tc("phrases.deleteQuestion"),
        text: question,
        showCancelButton: true,
      }).then(async (result) => {
        if (!result.value) return

        const rows = this.questions
          .filter((existing) => existing !== question)
          .map((existing) => ({
            question: existing,
            answer: this.answers[existing],
          }))

        const persisted = await this.persist(rows)
        if (!persisted) {
          return
        }

        this.alertNotify("success", this.$tc("phrases.questionRemoved"))
      })
    },
    async persist(rows) {
      this.saving = true
      const response = await this.personService.updateOnboarding(
        this.personId,
        rows.map((row) => ({
          question: row.question,
          answer: row.answer === null ? "" : String(row.answer),
        })),
      )
      this.saving = false

      if (response instanceof ErrorHandler) {
        this.alertNotify("error", response.errorMessage)
        return false
      }

      this.answers = response.onboarding_json ?? {}
      this.$emit("updated", this.answers)

      return true
    },
  },
}
</script>

<style lang="scss" scoped>
$primary: #2c5f88;
$primary-light: rgba(44, 95, 136, 0.08);
$border-subtle: rgba(0, 0, 0, 0.08);
$text-muted: rgba(0, 0, 0, 0.55);

.onboarding-empty {
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

// Each cell truncates on its own, so a long question can never squeeze the
// answer out of the row. The full text is in the dialog the cell opens.
.answer-cell {
  display: block;
  max-width: 22rem;
  cursor: pointer;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;

  &--question {
    font-weight: 500;
    color: rgba(0, 0, 0, 0.85);
  }
}

::v-deep .onboarding-dialog {
  .md-dialog-container {
    width: 520px;
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

  &__field {
    margin: 0;
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
</style>
