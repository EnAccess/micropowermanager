<template>
  <div class="whatsapp-page">
    <div class="page-heading">
      <div>
        <h1>Message templates</h1>
        <p>Create and preview the messages customers will receive.</p>
      </div>
      <md-button class="md-raised md-primary" @click="startNew">
        New template
      </md-button>
    </div>
    <div class="template-layout">
      <md-card>
        <md-card-header><div class="md-title">Templates</div></md-card-header>
        <md-list>
          <md-list-item
            v-for="template in templates"
            :key="template.id"
            @click="select(template)"
            :class="{ selected: selected && selected.id === template.id }"
          >
            <span class="md-list-item-text">
              <strong>{{ template.name }}</strong>
              <small>{{ template.type }} · {{ template.language }}</small>
            </span>
            <status-badge :status="template.status" />
          </md-list-item>
        </md-list>
        <div v-if="!templates.length" class="empty-state">
          No templates configured.
        </div>
      </md-card>
      <md-card>
        <md-card-header>
          <div class="md-title">
            {{ editing ? "Edit template" : "Template editor" }}
          </div>
        </md-card-header>
        <md-card-content>
          <div class="md-layout md-gutter">
            <div class="md-layout-item md-size-50 md-small-size-100">
              <md-field>
                <label>Name</label>
                <md-input v-model="form.name" />
              </md-field>
            </div>
            <div class="md-layout-item md-size-50 md-small-size-100">
              <md-field>
                <label>Type</label>
                <md-select v-model="form.type">
                  <md-option v-for="type in types" :key="type" :value="type">
                    {{ type }}
                  </md-option>
                </md-select>
              </md-field>
            </div>
          </div>
          <md-field>
            <label>Message body</label>
            <md-textarea v-model="form.body" md-autogrow />
          </md-field>
          <p class="section-label">Insert variable</p>
          <div class="variables">
            <md-chip
              v-for="variable in variables"
              :key="variable"
              md-clickable
              @click="insertVariable(variable)"
            >
              {{ variable }}
            </md-chip>
          </div>
          <div class="actions">
            <md-button
              class="md-raised md-primary"
              :disabled="!form.name || !form.body"
              @click="save"
            >
              Save template
            </md-button>
            <md-button v-if="editing" @click="startNew">Cancel</md-button>
          </div>
        </md-card-content>
      </md-card>
      <md-card>
        <md-card-header><div class="md-title">Preview</div></md-card-header>
        <md-card-content>
          <message-preview
            :message="
              form.body || 'Your WhatsApp message preview will appear here.'
            "
          />
        </md-card-content>
      </md-card>
    </div>
  </div>
</template>

<script>
import { whatsappService } from "../../services/WhatsAppService.js"
import MessagePreview from "../Components/MessagePreview.vue"
import StatusBadge from "../Components/StatusBadge.vue"

const blankTemplate = () => ({
  name: "",
  type: "Payment receipt",
  language: "English",
  body: "",
})

export default {
  name: "WhatsAppTemplates",
  components: { MessagePreview, StatusBadge },
  data() {
    return {
      editing: false,
      form: blankTemplate(),
      selected: null,
      templates: [],
      types: ["Payment receipt", "Low-balance warning", "Token delivery"],
      variables: [
        "{{customer_name}}",
        "{{payment_amount}}",
        "{{payment_date}}",
        "{{transaction_id}}",
        "{{token}}",
        "{{balance}}",
      ],
    }
  },
  async mounted() {
    this.templates = await whatsappService.getTemplates()
    if (this.templates.length) this.select(this.templates[0])
  },
  methods: {
    select(template) {
      this.selected = template
      this.form = { ...template }
      this.editing = true
    },
    startNew() {
      this.selected = null
      this.form = blankTemplate()
      this.editing = false
    },
    insertVariable(variable) {
      this.form.body += (this.form.body ? " " : "") + variable
    },
    async save() {
      const template = this.editing
        ? await whatsappService.updateTemplate(this.form.id, this.form)
        : await whatsappService.createTemplate(this.form)
      this.templates = await whatsappService.getTemplates()
      this.select(template)
    },
  },
}
</script>

<style lang="scss" scoped>
.whatsapp-page {
  padding: 1rem;
}
.page-heading {
  align-items: center;
  display: flex;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}
.page-heading h1 {
  margin: 0;
}
.page-heading p,
.md-list-item-text small {
  color: #607d8b;
  margin: 0.35rem 0 0;
}
.template-layout {
  display: grid;
  gap: 1rem;
  grid-template-columns: 0.9fr 1.5fr 1fr;
}
.md-list-item-text {
  display: flex;
  flex-direction: column;
}
.selected {
  background: #f1f8ff;
}
.section-label {
  color: #455a64;
  font-weight: 600;
}
.variables {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}
.actions {
  margin-top: 1rem;
}
.empty-state {
  color: #607d8b;
  padding: 1.5rem;
  text-align: center;
}
@media (max-width: 1000px) {
  .template-layout {
    grid-template-columns: 1fr 1fr;
  }
  .template-layout > :last-child {
    grid-column: span 2;
  }
}
@media (max-width: 600px) {
  .page-heading {
    align-items: flex-start;
    flex-direction: column;
    gap: 1rem;
  }
  .template-layout {
    grid-template-columns: 1fr;
  }
  .template-layout > :last-child {
    grid-column: auto;
  }
}
</style>
