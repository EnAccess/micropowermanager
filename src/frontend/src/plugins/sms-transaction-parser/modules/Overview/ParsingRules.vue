<template>
  <div>
    <md-card>
      <md-card-header>
        <div class="md-title">SMS Parsing Rules</div>
      </md-card-header>
      <md-card-content>
        <md-table v-if="rules.length > 0">
          <md-table-row>
            <md-table-head>Provider</md-table-head>
            <md-table-head>Sender Filter</md-table-head>
            <md-table-head>Enabled</md-table-head>
            <md-table-head>Actions</md-table-head>
          </md-table-row>
          <md-table-row v-for="rule in rules" :key="rule.id">
            <md-table-cell>{{ rule.provider_name }}</md-table-cell>
            <md-table-cell>{{ rule.sender_pattern || "-" }}</md-table-cell>
            <md-table-cell>
              <md-switch v-model="rule.enabled" @change="toggleEnabled(rule)" />
            </md-table-cell>
            <md-table-cell>
              <md-button class="md-icon-button" @click="editRule(rule)">
                <md-icon>edit</md-icon>
              </md-button>
              <md-button class="md-icon-button" @click="confirmDelete(rule)">
                <md-icon>delete</md-icon>
              </md-button>
            </md-table-cell>
          </md-table-row>
        </md-table>
        <p v-else>No parsing rules configured yet.</p>
      </md-card-content>
      <md-card-actions>
        <md-button class="md-raised md-primary" @click="openAddForm">
          Add Rule
        </md-button>
      </md-card-actions>
    </md-card>

    <md-dialog :md-active.sync="showForm" class="rule-dialog">
      <md-dialog-title>
        {{ editingRule ? "Edit" : "Add" }} Parsing Rule
      </md-dialog-title>
      <md-dialog-content>
        <div class="md-layout md-gutter top-fields-row">
          <div class="md-layout-item md-size-50">
            <md-field>
              <label>Provider Name</label>
              <md-input
                v-model="formData.provider_name"
                placeholder="e.g. vodacom"
              />
              <span class="md-helper-text">
                Identifier for this mobile money provider
              </span>
            </md-field>
          </div>
          <div class="md-layout-item md-size-50">
            <md-field>
              <label>Sender Filter (optional)</label>
              <md-input
                v-model="formData.sender_pattern"
                placeholder="e.g. M-Pesa"
              />
              <span class="md-helper-text">
                Only process SMS from this sender name
              </span>
            </md-field>
          </div>
        </div>

        <p class="section-label">SMS Message Template</p>
        <p class="section-hint">
          Paste a sample SMS message and replace the variable parts with the
          tags below. Click a tag to insert it at the end.
        </p>

        <div class="template-editor">
          <div class="variable-chips">
            <md-chip
              v-for="v in variables"
              :key="v.name"
              class="variable-chip"
              md-clickable
              @click="insertVariable(v.name)"
            >
              {{ v.label }}
            </md-chip>
            <md-chip
              class="variable-chip wildcard-chip"
              md-clickable
              @click="insertVariable(wildcard.name)"
            >
              {{ wildcard.label }}
            </md-chip>
          </div>
          <md-field>
            <label>Message template</label>
            <md-textarea
              ref="templateInput"
              v-model="formData.template"
              md-autogrow
              class="template-input"
              placeholder="e.g. Confirmed [transaction_ref].[*]amount of [amount]MT[*]reference [device_serial][*]"
            />
          </md-field>
        </div>

        <md-card v-if="previewMessage" class="preview-card">
          <md-card-content>
            <p class="preview-label">Preview with sample values:</p>
            <p class="preview-text">{{ previewMessage }}</p>
          </md-card-content>
        </md-card>

        <div class="test-section">
          <p class="section-label">Test with a real SMS</p>
          <md-field>
            <label>Paste an actual SMS message to verify it matches</label>
            <md-textarea v-model="testSms" md-autogrow />
          </md-field>
          <md-button
            class="md-raised md-dense"
            :disabled="!formData.template || !testSms"
            @click="testTemplate"
          >
            Test
          </md-button>
          <div v-if="testResult !== null" class="test-result">
            <div v-if="testResult" class="test-success">
              <md-icon>check_circle</md-icon>
              <div>
                <div>
                  <strong>Match found</strong>
                </div>
                <div class="extracted-values">
                  <span v-for="v in variables" :key="v.name">
                    {{ v.label }}:
                    <strong>{{ testResult[v.name] || "—" }}</strong>
                  </span>
                </div>
              </div>
            </div>
            <div v-else class="test-fail">
              <md-icon>error</md-icon>
              <span>No match. Check your template and sample message.</span>
            </div>
          </div>
        </div>

        <md-switch v-model="formData.enabled">Enabled</md-switch>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button @click="cancelForm">Cancel</md-button>
        <md-button class="md-primary" @click="saveRule">Save</md-button>
      </md-dialog-actions>
    </md-dialog>
  </div>
</template>

<script>
import { ParsingRuleService } from "../../services/ParsingRuleService"
import { notify } from "@/mixins/notify"

const REQUIRED_VARIABLES = [
  {
    name: "transaction_ref",
    label: "Transaction Ref",
    sample: "ABC123XYZ",
    regex: "[A-Za-z0-9.]+",
  },
  {
    name: "amount",
    label: "Amount",
    sample: "5,000.00",
    regex: "[\\d,]+(?:\\.\\d{1,2})?",
  },
  {
    name: "device_serial",
    label: "Meter / Account",
    sample: "SN12345",
    regex: "[A-Za-z0-9]+",
  },
]

const OPTIONAL_VARIABLES = [
  {
    name: "sender_phone",
    label: "Sender Phone",
    sample: "258841234567",
    regex: "[\\d+\\s\\-]+",
  },
]

const WILDCARD = {
  name: "*",
  label: "Wildcard [*]",
}

const ALL_VARIABLES = [...REQUIRED_VARIABLES, ...OPTIONAL_VARIABLES]

export default {
  name: "ParsingRules",
  mixins: [notify],
  data() {
    return {
      parsingRuleService: new ParsingRuleService(),
      rules: [],
      showForm: false,
      editingRule: null,
      variables: ALL_VARIABLES,
      wildcard: WILDCARD,
      formData: {
        provider_name: "",
        template: "",
        sender_pattern: "",
        enabled: false,
      },
      testSms: "",
      testResult: null,
    }
  },
  computed: {
    previewMessage() {
      if (!this.formData.template) return ""
      let msg = this.formData.template
      ALL_VARIABLES.forEach((v) => {
        msg = msg.replaceAll(`[${v.name}]`, v.sample)
      })
      msg = msg.replaceAll("[*]", "...")
      return msg
    },
  },
  mounted() {
    this.loadRules()
  },
  methods: {
    async loadRules() {
      try {
        this.rules = await this.parsingRuleService.getRules()
      } catch (e) {
        this.alertNotify("error", "Failed to load parsing rules")
      }
    },
    openAddForm() {
      this.editingRule = null
      this.formData = {
        provider_name: "",
        template: "",
        sender_pattern: "",
        enabled: false,
      }
      this.testSms = ""
      this.testResult = null
      this.showForm = true
    },
    editRule(rule) {
      this.editingRule = rule
      this.formData = {
        provider_name: rule.provider_name,
        template: rule.template || "",
        sender_pattern: rule.sender_pattern || "",
        enabled: rule.enabled,
      }
      this.testSms = ""
      this.testResult = null
      this.showForm = true
    },
    cancelForm() {
      this.showForm = false
      this.editingRule = null
    },
    insertVariable(name) {
      this.formData.template += `[${name}]`
    },
    async saveRule() {
      const missing = REQUIRED_VARIABLES.filter(
        (v) => !this.formData.template.includes(`[${v.name}]`),
      )
      if (missing.length > 0) {
        this.alertNotify(
          "error",
          `Template must include: ${missing.map((v) => v.label).join(", ")}`,
        )
        return
      }
      try {
        if (this.editingRule) {
          await this.parsingRuleService.updateRule(
            this.editingRule.id,
            this.formData,
          )
          this.alertNotify("success", "Rule updated")
        } else {
          await this.parsingRuleService.createRule(this.formData)
          this.alertNotify("success", "Rule created")
        }
        this.cancelForm()
        await this.loadRules()
      } catch (e) {
        this.alertNotify("error", "Failed to save rule")
      }
    },
    async toggleEnabled(rule) {
      try {
        await this.parsingRuleService.updateRule(rule.id, {
          provider_name: rule.provider_name,
          template: rule.template,
          sender_pattern: rule.sender_pattern,
          enabled: rule.enabled,
        })
        await this.loadRules()
      } catch (e) {
        this.alertNotify("error", "Failed to toggle rule")
      }
    },
    async confirmDelete(rule) {
      if (!confirm(`Delete rule "${rule.provider_name}"?`)) return
      try {
        await this.parsingRuleService.deleteRule(rule.id)
        this.alertNotify("success", "Rule deleted")
        await this.loadRules()
      } catch (e) {
        this.alertNotify("error", "Failed to delete rule")
      }
    },
    testTemplate() {
      try {
        const template = this.formData.template
        // Split template into literal segments and placeholder names (including *)
        const parts = template.split(/\[(\w+|\*)]/)
        // parts alternates: literal, varName, literal, varName, ...
        let regexStr = ""
        for (let i = 0; i < parts.length; i++) {
          if (i % 2 === 0) {
            // Literal text — escape for regex, collapse whitespace
            let literal = parts[i].replace(/[-/\\^$*+?.()|{}[\]]/g, "\\$&")
            literal = literal.replace(/\s+/g, "\\s+")
            regexStr += literal
          } else if (parts[i] === "*") {
            // Wildcard — match anything lazily
            regexStr += "[\\s\\S]*?"
          } else {
            // Variable name — insert named capture group
            const name = parts[i]
            const v = ALL_VARIABLES.find((v) => v.name === name)
            const pattern = v ? v.regex : ".+?"
            regexStr += `(?<${name}>${pattern})`
          }
        }
        const regex = new RegExp(regexStr, "si")
        const result = regex.exec(this.testSms)
        if (result && result.groups) {
          this.testResult = { ...result.groups }
        } else {
          this.testResult = false
        }
      } catch (e) {
        this.testResult = false
      }
    },
  },
}
</script>

<style scoped>
.rule-dialog .md-dialog-container {
  min-width: 680px;
  max-width: 800px;
}

.top-fields-row {
  margin-bottom: 36px;
}

.section-label {
  font-weight: 600;
  font-size: 14px;
  color: #333;
  margin: 0 0 4px;
}

.section-hint {
  font-size: 13px;
  color: #777;
  margin: 0 0 12px;
}

.template-editor {
  margin-bottom: 8px;
}

.variable-chips {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  margin-bottom: 8px;
}

.variable-chip {
  cursor: pointer;
}

.wildcard-chip {
  background-color: #e0e0e0 !important;
  font-style: italic;
}

.template-input textarea {
  font-family: monospace;
  font-size: 13px;
  line-height: 1.5;
}

.preview-card {
  background: #e3f2fd;
  margin: 0 0 16px;
}

.preview-label {
  font-size: 12px;
  color: #666;
  margin: 0 0 4px;
}

.preview-text {
  font-size: 14px;
  color: #333;
  margin: 0;
  word-break: break-word;
}

.test-section {
  margin: 16px 0 8px;
  padding-top: 12px;
  border-top: 1px solid #eee;
}

.test-result {
  margin-top: 8px;
}

.test-success {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  color: #4caf50;
  font-size: 13px;
}

.test-success strong {
  color: #333;
}

.extracted-values {
  display: flex;
  flex-wrap: wrap;
  gap: 4px 16px;
  margin-top: 4px;
}

.test-fail {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #f44336;
  font-size: 13px;
}
</style>
