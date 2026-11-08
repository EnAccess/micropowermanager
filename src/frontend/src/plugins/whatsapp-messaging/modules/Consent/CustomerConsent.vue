<template>
  <div v-if="isPluginEnabled">
    <widget
      id="client-whatsapp-consent"
      :title="widgetTitle"
      color="primary"
      :button-text="buttonText"
      :button-icon="isOptedIn ? 'unsubscribe' : 'how_to_reg'"
      @widgetAction="openDialog"
    >
      <div v-if="loaded && consentLogs.length === 0" class="consent-empty">
        <md-icon class="consent-empty__icon md-size-2x">forum</md-icon>
        <p class="consent-empty__text">
          {{ $t("phrases.whatsappNoConsentHistory") }}
        </p>
      </div>
      <md-table
        v-else-if="consentLogs.length"
        style="width: 100%"
        :value="consentLogs"
        md-card
        md-fixed-header
      >
        <md-table-row slot="md-table-row" slot-scope="{ item }">
          <md-table-cell :md-label="$tc('words.date')">
            {{ timeForTimeZone(item.created_at) }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.actions')">
            <span :class="item.action === 'opt_in' ? 'opted_in' : 'opted_out'">
              {{
                item.action === "opt_in"
                  ? $t("phrases.whatsappOptedIn")
                  : $t("phrases.whatsappOptedOut")
              }}
            </span>
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.source')">
            {{
              item.source === "keyword"
                ? $t("phrases.whatsappKeywordSource")
                : $t("phrases.whatsappOperatorSource")
            }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.user')">
            {{ item.user?.name ?? "—" }}
          </md-table-cell>
          <md-table-cell :md-label="$t('phrases.whatsappNoteOrMessage')">
            {{ item.raw_message || "—" }}
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>

    <md-dialog :md-active.sync="dialogActive">
      <md-dialog-title>
        {{
          isOptedIn
            ? $t("phrases.whatsappOptOutQuestion", { name: personName })
            : $t("phrases.whatsappOptInCustomer")
        }}
      </md-dialog-title>
      <md-dialog-content>
        <p v-if="isOptedIn">{{ $t("phrases.whatsappOptOutExplanation") }}</p>
        <md-field>
          <label>
            {{
              isOptedIn
                ? $t("phrases.whatsappOptionalNote")
                : $t("phrases.whatsappConsentNote")
            }}
          </label>
          <md-textarea v-model="consentNote" maxlength="500" md-counter="500" />
        </md-field>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button @click="dialogActive = false">
          {{ $tc("words.cancel") }}
        </md-button>
        <md-button
          class="md-raised"
          :class="isOptedIn ? 'md-accent' : 'md-primary'"
          :disabled="saving || (!isOptedIn && !consentNote.trim())"
          @click="confirm"
        >
          {{ buttonText }}
        </md-button>
      </md-dialog-actions>
    </md-dialog>
  </div>
</template>

<script>
import { mapGetters } from "vuex"

import { WhatsappContactService } from "../../services/WhatsappContactService.js"

import { notify } from "@/mixins/notify.js"
import { timing } from "@/mixins/timing.js"
import Widget from "@/shared/Widget.vue"

// Same id as MpmPlugin::WHATSAPP_MESSAGING in the backend.
const WHATSAPP_MESSAGING_PLUGIN_ID = 33

export default {
  name: "CustomerConsent",
  components: { Widget },
  mixins: [notify, timing],
  props: {
    person: { type: Object, required: true },
  },
  data() {
    return {
      contactService: new WhatsappContactService(),
      loaded: false,
      contact: null,
      dialogActive: false,
      consentNote: "",
      saving: false,
    }
  },
  computed: {
    ...mapGetters("settings", ["getEnabledPlugins"]),
    isPluginEnabled() {
      return this.getEnabledPlugins.includes(WHATSAPP_MESSAGING_PLUGIN_ID)
    },
    isOptedIn() {
      return this.contact?.opt_in_status === "opted_in"
    },
    // The status sits in the title, like the document count of the Documents card.
    widgetTitle() {
      const title = this.$t("phrases.whatsappConsent")
      if (!this.loaded) {
        return title
      }
      if (this.contact === null) {
        return `${title}  ·  ${this.$t("phrases.whatsappNotOptedIn")}`
      }
      const status = this.isOptedIn
        ? this.$t("phrases.whatsappOptedIn")
        : this.$t("phrases.whatsappOptedOut")
      return `${title}  ·  ${status}`
    },
    // Only one action is offered, so the opt-in button disappears once the customer is opted in.
    buttonText() {
      if (!this.loaded) {
        return null
      }
      return this.isOptedIn
        ? this.$t("phrases.whatsappOptOut")
        : this.$t("phrases.whatsappOptIn")
    },
    consentLogs() {
      return this.contact?.consent_logs ?? []
    },
    personName() {
      return `${this.person.name} ${this.person.surname}`
    },
  },
  watch: {
    isPluginEnabled: {
      immediate: true,
      handler(enabled) {
        if (enabled) {
          this.loadContact()
        }
      },
    },
  },
  methods: {
    async loadContact() {
      try {
        this.contact = await this.contactService.getContact(this.person.id)
        this.loaded = true
      } catch (error) {
        this.alertNotify(
          "error",
          error.response?.data?.message || error.message,
        )
      }
    },
    openDialog() {
      this.consentNote = ""
      this.dialogActive = true
    },
    async confirm() {
      const note = this.consentNote.trim()
      this.saving = true
      try {
        if (this.isOptedIn) {
          this.contact = await this.contactService.optOut(this.person.id, note)
          this.alertNotify(
            "success",
            this.$t("phrases.whatsappCustomerOptedOut"),
          )
        } else {
          this.contact = await this.contactService.optIn(this.person.id, note)
          this.alertNotify(
            "success",
            this.$t("phrases.whatsappCustomerOptedIn"),
          )
        }
        this.dialogActive = false
      } catch (error) {
        this.alertNotify(
          "error",
          error.response?.data?.message || error.message,
        )
      } finally {
        this.saving = false
      }
    },
  },
}
</script>

<style lang="scss" scoped>
.consent-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2.5rem 1rem;
  color: rgba(0, 0, 0, 0.55);

  // Matches the empty-state icon of the Documents and Onboarding cards; .md-icon raises specificity above the theme colour.
  &__icon.md-icon {
    color: rgba(0, 0, 0, 0.25);
    margin-bottom: 0.5rem;
  }

  &__text {
    margin: 0;
    font-size: 0.95rem;
  }
}

.opted_in {
  color: #4caf50;
}

.opted_out {
  color: #f44336;
}
</style>
