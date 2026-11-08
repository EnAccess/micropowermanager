<template>
  <div v-if="templateService.manualTemplates.length">
    <md-field>
      <label>{{ $t("phrases.whatsappTemplatesMessageType") }}</label>
      <md-select
        ref="messageType"
        v-model="templateId"
        @md-selected="emitChange"
        @md-opened="closeOnScroll(true)"
        @md-closed="closeOnScroll(false)"
      >
        <md-option :value="FREE_TEXT">
          {{ $t("phrases.whatsappTemplatesFreeText") }}
        </md-option>
        <md-option
          v-for="template in templateService.manualTemplates"
          :key="template.id"
          :value="template.id"
        >
          {{ template.name }} ({{ template.language }}, {{ template.category }})
        </md-option>
      </md-select>
    </md-field>

    <template v-if="template">
      <p v-if="template.category === 'MARKETING'" class="md-caption warning">
        {{ $t("phrases.whatsappTemplatesMarketingWarning") }}
      </p>
      <md-field v-for="parameter in fillableParameters" :key="parameter">
        <label>{{ parameter }}</label>
        <md-input v-model="values[parameter]" @input="emitChange" />
      </md-field>
      <p v-if="fillsCustomerName" class="md-caption">
        {{ $t("phrases.whatsappTemplatesCustomerNameFilled") }}
      </p>
      <p class="md-caption">{{ $t("phrases.whatsappTemplatesPreview") }}</p>
      <div class="preview">{{ preview }}</div>
      <p class="md-caption">
        {{ $t("phrases.whatsappTemplatesSmsFallbackHint") }}
      </p>
    </template>
  </div>
</template>

<script>
import { WhatsappTemplateService } from "@/plugins/whatsapp-messaging/services/WhatsappTemplateService.js"

const FREE_TEXT = "free-text"
// The backend fills these in per receiver, so one bulk send greets every customer by name.
const CUSTOMER_PARAMETERS = ["name", "surname"]
const PARAMETER_PATTERN = /{{\s*(\w+)\s*}}/g

export default {
  name: "WhatsappTemplateFields",
  props: {
    value: {
      type: Object,
      default: () => ({ templateId: null, values: {}, isComplete: true }),
    },
  },
  data() {
    return {
      FREE_TEXT,
      templateService: new WhatsappTemplateService(),
      templateId: FREE_TEXT,
      values: {},
    }
  },
  computed: {
    template() {
      return this.templateService.manualTemplates.find(
        (template) => template.id === this.templateId,
      )
    },
    fillableParameters() {
      return (this.template?.parameters ?? []).filter(
        (parameter) => !CUSTOMER_PARAMETERS.includes(parameter),
      )
    },
    fillsCustomerName() {
      return (this.template?.parameters ?? []).some((parameter) =>
        CUSTOMER_PARAMETERS.includes(parameter),
      )
    },
    preview() {
      const fill = (text) =>
        text.replace(PARAMETER_PATTERN, (placeholder, parameter) =>
          CUSTOMER_PARAMETERS.includes(parameter)
            ? placeholder
            : this.values[parameter] || placeholder,
        )
      return [this.template.header, this.template.body]
        .filter(Boolean)
        .map(fill)
        .join("\n")
    },
  },
  watch: {
    // The parent clears the form after sending by resetting its value.
    value(newValue) {
      if (newValue.templateId === null && this.templateId !== FREE_TEXT) {
        this.templateId = FREE_TEXT
        this.values = {}
      }
    },
  },
  // Without the plugin or any usable template the list stays empty and the picker is not shown.
  async mounted() {
    try {
      await this.templateService.getManualTemplates()
    } catch {
      this.templateService.manualTemplates = []
    }
  },
  beforeDestroy() {
    this.closeOnScroll(false)
  },
  methods: {
    // Vue Material pins the open menu to the window, so it would float in place while the page scrolls; closing it keeps it with the field.
    closeOnScroll(isOpen) {
      if (isOpen) {
        window.addEventListener("scroll", this.closeMenu, true)
      } else {
        window.removeEventListener("scroll", this.closeMenu, true)
      }
    },
    closeMenu(event) {
      // Scrolling inside the open menu's own list is fine.
      if (event.target?.closest?.(".md-menu-content")) return
      if (this.$refs.messageType) {
        this.$refs.messageType.showSelect = false
      }
    },
    emitChange() {
      const isTemplate = this.templateId !== FREE_TEXT
      this.$emit("input", {
        templateId: isTemplate ? this.templateId : null,
        values: isTemplate
          ? Object.fromEntries(
              this.fillableParameters.map((parameter) => [
                parameter,
                this.values[parameter] ?? "",
              ]),
            )
          : {},
        isComplete:
          !isTemplate ||
          this.fillableParameters.every((parameter) =>
            (this.values[parameter] ?? "").trim(),
          ),
      })
    },
  },
}
</script>

<style lang="scss" scoped>
.preview {
  padding: 0.5rem 0.75rem;
  border-left: 3px solid #25d366;
  background: rgba(0, 0, 0, 0.03);
  white-space: pre-line;
}

.warning {
  color: #b26a00;
}
</style>
