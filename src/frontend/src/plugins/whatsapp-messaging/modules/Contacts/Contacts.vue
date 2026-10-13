<template>
  <div>
    <div class="md-layout md-gutter">
      <div class="md-layout-item md-size-30">
        <md-field>
          <label>Opt-in status</label>
          <md-select v-model="status">
            <md-option value="all">All</md-option>
            <md-option
              v-for="(label, value) in statusLabels"
              :key="value"
              :value="value"
            >
              {{ label }}
            </md-option>
          </md-select>
        </md-field>
      </div>
    </div>

    <widget
      :key="widgetKey"
      title="WhatsApp Contacts"
      color="primary"
      :paginator="paginator"
      :subscriber="subscribers.contacts"
      :show_per_page="true"
      :button="true"
      button-text="Opt in a customer"
      button-icon="person_add"
      @widgetAction="openOptIn(null)"
    >
      <md-table v-model="contacts" md-card md-fixed-header>
        <md-table-row slot="md-table-row" slot-scope="{ item }">
          <md-table-cell md-label="Customer">
            {{ item.person_name || "Unknown" }}
          </md-table-cell>
          <md-table-cell md-label="Phone">{{ item.phone }}</md-table-cell>
          <md-table-cell md-label="Status">
            <span :class="item.opt_in_status">
              {{ statusLabels[item.opt_in_status] }}
            </span>
          </md-table-cell>
          <md-table-cell md-label="Opted in">
            {{ formatDate(item.opted_in_at) }}
          </md-table-cell>
          <md-table-cell md-label="Opted out">
            {{ formatDate(item.opted_out_at) }}
          </md-table-cell>
          <md-table-cell md-label="Last inbound message">
            {{ formatDate(item.last_inbound_at) }}
          </md-table-cell>
          <md-table-cell md-label="Actions">
            <md-button class="md-icon-button" @click="historyContact = item">
              <md-tooltip>Consent history</md-tooltip>
              <md-icon>history</md-icon>
            </md-button>
            <template v-if="item.person_id">
              <md-button
                v-if="item.opt_in_status === 'opted_in'"
                class="md-icon-button md-accent"
                @click="openOptOut(item)"
              >
                <md-tooltip>Opt out</md-tooltip>
                <md-icon>unsubscribe</md-icon>
              </md-button>
              <md-button
                v-else
                class="md-icon-button md-primary"
                @click="openOptIn(item.person_id)"
              >
                <md-tooltip>Opt in</md-tooltip>
                <md-icon>how_to_reg</md-icon>
              </md-button>
            </template>
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>

    <md-dialog :md-active.sync="optInDialogActive">
      <md-dialog-title>Opt in a customer</md-dialog-title>
      <md-dialog-content>
        <md-field>
          <label>Customer (person) ID</label>
          <md-input
            v-model="personId"
            type="number"
            @keyup.enter="findPerson"
          />
          <md-button class="md-icon-button md-dense" @click="findPerson">
            <md-icon>search</md-icon>
          </md-button>
        </md-field>
        <p v-if="person">
          <strong>{{ person.name }} {{ person.surname }}</strong>
          <span v-if="primaryPhone">· {{ primaryPhone }}</span>
        </p>
        <md-field>
          <label>Phone override (optional, international)</label>
          <md-input v-model="phone" placeholder="+255712345678" />
        </md-field>
        <md-field>
          <label>Consent note</label>
          <md-textarea v-model="consentNote" maxlength="500" md-counter="500" />
        </md-field>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button @click="optInDialogActive = false">Cancel</md-button>
        <md-button
          class="md-primary md-raised"
          :disabled="!person || !consentNote.trim() || saving"
          @click="confirmOptIn"
        >
          Opt in
        </md-button>
      </md-dialog-actions>
    </md-dialog>

    <md-dialog :md-active.sync="optOutDialogActive">
      <md-dialog-title>
        Opt out {{ optOutContact && optOutContact.person_name }}?
      </md-dialog-title>
      <md-dialog-content>
        <p>
          The customer stops receiving WhatsApp messages until they opt in
          again.
        </p>
        <md-field>
          <label>Note (optional)</label>
          <md-textarea v-model="consentNote" maxlength="500" md-counter="500" />
        </md-field>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button @click="optOutDialogActive = false">Cancel</md-button>
        <md-button
          class="md-accent md-raised"
          :disabled="saving"
          @click="confirmOptOut"
        >
          Opt out
        </md-button>
      </md-dialog-actions>
    </md-dialog>

    <md-dialog
      :md-active="historyContact !== null"
      class="history-dialog"
      @update:mdActive="historyContact = null"
    >
      <md-dialog-title>
        Consent history ·
        {{
          historyContact && (historyContact.person_name || historyContact.phone)
        }}
      </md-dialog-title>
      <md-dialog-content>
        <widget
          v-if="historyContact"
          :key="historyContact.id"
          :paginator="historyPaginator"
          :subscriber="subscribers.consentLogs"
        >
          <md-table v-model="consentLogs">
            <md-table-row slot="md-table-row" slot-scope="{ item }">
              <md-table-cell md-label="Date">
                {{ formatDate(item.created_at) }}
              </md-table-cell>
              <md-table-cell md-label="Action">
                <span
                  :class="item.action === 'opt_in' ? 'opted_in' : 'opted_out'"
                >
                  {{ item.action === "opt_in" ? "Opted in" : "Opted out" }}
                </span>
              </md-table-cell>
              <md-table-cell md-label="Source">
                {{
                  item.source === "keyword" ? "Customer (keyword)" : "Operator"
                }}
              </md-table-cell>
              <md-table-cell md-label="User">
                {{ item.user_id ? `#${item.user_id}` : "—" }}
              </md-table-cell>
              <md-table-cell md-label="Note / message">
                {{ item.raw_message || "—" }}
              </md-table-cell>
            </md-table-row>
          </md-table>
        </widget>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button @click="historyContact = null">Close</md-button>
      </md-dialog-actions>
    </md-dialog>
  </div>
</template>

<script>
import { WhatsappContactService } from "../../services/WhatsappContactService.js"

import { notify } from "@/mixins/notify.js"
import { timing } from "@/mixins/timing.js"
import { PersonService } from "@/services/PersonService.js"
import { EventBus } from "@/shared/eventbus.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "Contacts",
  components: { Widget },
  mixins: [notify, timing],
  data() {
    return {
      contactService: new WhatsappContactService(),
      personService: new PersonService(),
      statusLabels: { opted_in: "Opted in", opted_out: "Opted out" },
      subscribers: {
        contacts: "whatsapp-contacts",
        consentLogs: "whatsapp-consent-logs",
      },
      status: "all",
      reloadCount: 0,
      contacts: [],
      optInDialogActive: false,
      personId: "",
      person: null,
      phone: "",
      consentNote: "",
      optOutDialogActive: false,
      optOutContact: null,
      historyContact: null,
      consentLogs: [],
      saving: false,
    }
  },
  computed: {
    paginator() {
      return this.contactService.contactsPaginator(
        this.status === "all" ? null : this.status,
      )
    },
    // Widget re-mounts its Paginate on key change, which reloads the list from page 1
    widgetKey() {
      return `${this.status}-${this.reloadCount}`
    },
    historyPaginator() {
      return this.historyContact
        ? this.contactService.consentLogsPaginator(this.historyContact.id)
        : null
    },
    primaryPhone() {
      const addresses = this.person?.addresses ?? []
      return (addresses.find((address) => address.is_primary) ?? addresses[0])
        ?.phone
    },
  },
  mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
  },
  methods: {
    reloadList(subscriber, data) {
      if (subscriber === this.subscribers.contacts) {
        this.contacts = data
      } else if (subscriber === this.subscribers.consentLogs) {
        this.consentLogs = data
      } else {
        return
      }
      EventBus.$emit("widgetContentLoaded", subscriber, data.length)
    },
    formatDate(date) {
      return date ? this.timeForTimeZone(date) : "—"
    },
    openOptIn(personId) {
      this.personId = personId ?? ""
      this.person = null
      this.phone = ""
      this.consentNote = ""
      this.optInDialogActive = true
      if (personId) this.findPerson()
    },
    async findPerson() {
      this.person = null
      if (!this.personId) return
      try {
        this.person = await this.personService.getPerson(this.personId)
      } catch (error) {
        this.alertNotify("error", error.message || "Customer not found")
      }
    },
    openOptOut(contact) {
      this.optOutContact = contact
      this.consentNote = ""
      this.optOutDialogActive = true
    },
    confirmOptIn() {
      const note = this.consentNote.trim()
      const request = this.contactService.optIn(
        this.person.id,
        this.phone.trim(),
        note,
      )
      return this.saveConsent(request, "Customer opted in to WhatsApp")
    },
    confirmOptOut() {
      const note = this.consentNote.trim()
      const request = this.contactService.optOut(
        this.optOutContact.person_id,
        note,
      )
      return this.saveConsent(request, "Customer opted out of WhatsApp")
    },
    async saveConsent(request, successMessage) {
      this.saving = true
      try {
        await request
        this.alertNotify("success", successMessage)
        this.optInDialogActive = false
        this.optOutDialogActive = false
        this.reloadCount++
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
.history-dialog {
  min-width: 60%;
}

.opted_in {
  color: #4caf50;
}

.opted_out {
  color: #f44336;
}
</style>
