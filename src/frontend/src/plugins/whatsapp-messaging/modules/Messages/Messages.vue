<template>
  <div>
    <div class="md-layout md-gutter message-filters">
      <md-field class="md-layout-item md-size-20 md-small-size-50">
        <label>{{ $tc("words.status") }}</label>
        <md-select v-model="filters.status">
          <md-option
            v-for="(icon, status) in statusIcons"
            :key="status"
            :value="status"
          >
            {{ statusLabels[status] }}
          </md-option>
        </md-select>
      </md-field>
      <md-field class="md-layout-item md-size-20 md-small-size-50">
        <label>{{ $tc("words.phone") }}</label>
        <md-input v-model="filters.phone" />
      </md-field>
      <md-datepicker
        v-for="field in ['from', 'to']"
        :key="field"
        v-model="filters[field]"
        class="md-layout-item md-size-20 md-small-size-50"
        md-immediately
      >
        <label>{{ $tc(`words.${field}`) }}</label>
      </md-datepicker>
      <div class="md-layout-item md-size-20">
        <md-button class="md-raised" @click="filters = emptyFilters()">
          {{ $tc("words.clear") }}
        </md-button>
      </div>
    </div>

    <widget
      :title="$t('phrases.whatsappMessagesLogs')"
      color="primary"
      :paginator="paginator"
      :subscriber="subscriber"
      :show_per_page="true"
      :max_per_page="100"
    >
      <md-table v-model="messages" md-card>
        <md-table-row
          slot="md-table-row"
          slot-scope="{ item }"
          @click="toggleDetails(item)"
        >
          <md-table-cell :md-label="$tc('words.customer')">
            {{ item.customer_name }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.receiver')">
            {{ item.receiver }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.message')" :title="item.body">
            {{ truncate(item.body) }}
            <div v-if="expandedId === item.id" class="md-caption">
              <strong>
                {{ $t("phrases.whatsappErrorCode", { code: item.error_code }) }}
              </strong>
              {{ item.error_message || "—" }}
              <div>
                <strong>{{ channelLabel(item) }}</strong>
                <sms-delivery-status
                  :status="item.sms_status"
                  :error-message="item.sms_error_message"
                />
              </div>
            </div>
          </md-table-cell>
          <md-table-cell :md-label="$t('phrases.whatsappTemplate')">
            {{ item.template_name }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.type')">
            {{ item.message_type }}
          </md-table-cell>
          <md-table-cell
            :md-label="$tc('words.status')"
            :title="statusLabels[item.whatsapp_status]"
          >
            <md-icon :class="`status--${item.whatsapp_status}`">
              {{ statusIcons[item.whatsapp_status] }}
            </md-icon>
          </md-table-cell>
          <md-table-cell :md-label="$t('phrases.whatsappChannel')">
            <span :class="`channel channel--${channelClass(item)}`">
              {{ channelLabel(item) }}
            </span>
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.time')">
            {{ formatDateTime(item.created_at) }}
            <div class="md-caption">{{ lastEvent(item) }}</div>
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
  </div>
</template>

<script>
import moment from "moment"

import { Paginator } from "@/Helpers/Paginator.js"
import { notify } from "@/mixins/notify.js"
import { SmsGatewayService } from "@/services/SmsGatewayService.js"
import { EventBus } from "@/shared/eventbus.js"
import SmsDeliveryStatus from "@/shared/SmsDeliveryStatus.vue"
import Widget from "@/shared/Widget.vue"

const debounce = require("debounce")

const SMS_STATUS_SENT = 1
const SMS_STATUS_DELIVERED = 2

export default {
  name: "Messages",
  components: { SmsDeliveryStatus, Widget },
  mixins: [notify],
  data() {
    return {
      smsGatewayService: new SmsGatewayService(),
      areSmsGatewaysLoaded: false,
      paginator: new Paginator("/api/whatsapp-messaging/messages"),
      subscriber: "whatsapp-messages",
      messages: [],
      expandedId: null,
      filters: this.emptyFilters(),
      statusIcons: {
        accepted: "schedule",
        sent: "check",
        delivered: "done_all",
        read: "done_all",
        failed: "error",
      },
    }
  },
  computed: {
    statusLabels() {
      return {
        accepted: this.$t("phrases.whatsappStatusAccepted"),
        sent: this.$t("phrases.whatsappStatusSent"),
        delivered: this.$t("phrases.whatsappStatusDelivered"),
        read: this.$t("phrases.whatsappStatusRead"),
        failed: this.$t("phrases.whatsappStatusFailed"),
      }
    },
    // The available list only holds active gateway plugins, so a configured but disabled gateway counts as none.
    configuredSmsGateway() {
      const { smsGatewayId } = this.$store.getters["settings/getMainSettings"]
      return this.smsGatewayService.availableGateways.find(
        (gateway) => gateway.id === Number(smsGatewayId),
      )
    },
  },
  watch: {
    filters: {
      handler: debounce(function () {
        this.applyFilters()
      }, 400),
      deep: true,
    },
  },
  async mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
    // The gateway list needs the settings permission, which the messages list does not.
    if (!this.$store.getters["auth/getPermissions"]?.includes("settings")) {
      return
    }
    try {
      await this.smsGatewayService.getAvailableGateways()
      this.areSmsGatewaysLoaded = true
    } catch (e) {
      this.alertNotify("error", e.message)
    }
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
  },
  methods: {
    emptyFilters() {
      return { status: "", phone: "", from: null, to: null }
    },
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) return
      this.messages = data
      EventBus.$emit("widgetContentLoaded", this.subscriber, data.length)
    },
    applyFilters() {
      const { status, phone, from, to } = this.filters
      const term = Object.entries({
        status,
        phone: phone.trim(),
        from: from && moment(from).format("YYYY-MM-DD"),
        to: to && moment(to).format("YYYY-MM-DD"),
      }).filter(([, value]) => value)
      EventBus.$emit("loadPage", this.paginator, Object.fromEntries(term))
    },
    toggleDetails(item) {
      if (item.whatsapp_status !== "failed") return
      this.expandedId = this.expandedId === item.id ? null : item.id
    },
    // A failed WhatsApp message keeps a stored or failed SMS status until a gateway sends it, so only sent or delivered proves the fallback ran.
    isSentBySms(item) {
      return [SMS_STATUS_SENT, SMS_STATUS_DELIVERED].includes(item.sms_status)
    },
    channelClass(item) {
      if (item.whatsapp_status !== "failed") return "whatsapp"
      return this.isSentBySms(item) ? "sms" : "unsent"
    },
    channelLabel(item) {
      if (item.whatsapp_status !== "failed") return "WhatsApp"
      const gatewayLabel = this.configuredSmsGateway?.label
      if (this.isSentBySms(item))
        return this.$t("phrases.whatsappSentBy", {
          gateway: gatewayLabel ?? "SMS",
        })
      if (gatewayLabel)
        return this.$t("phrases.whatsappNotSentBy", { gateway: gatewayLabel })
      if (this.areSmsGatewaysLoaded)
        return this.$t("phrases.whatsappNoSmsGateway")
      return this.$t("phrases.whatsappNotSent")
    },
    truncate(text) {
      return text?.length > 40 ? `${text.slice(0, 40)}…` : text
    },
    formatDateTime(date) {
      return date ? moment(date).format("YYYY-MM-DD HH:mm") : ""
    },
    lastEvent(item) {
      const event = ["failed", "read", "delivered", "sent"].find(
        (name) => item[`${name}_at`],
      )
      return event
        ? `${this.statusLabels[event]} ${this.formatDateTime(item[`${event}_at`])}`
        : ""
    },
  },
}
</script>

<style lang="scss" scoped>
// Doubled with .md-icon to outrank Vue Material's theme colour without !important.
.md-icon.status--read {
  color: #34b7f1;
}

.md-icon.status--failed {
  color: #f44336;
}

.channel {
  padding: 2px 8px;
  border-radius: 12px;
  color: #fff;
  font-size: 12px;
  background-color: #25d366;
}

.channel--sms {
  background-color: #ff9800;
}

.channel--unsent {
  background-color: #f44336;
}
.message-filters {
  padding-left: 24px;
  padding-right: 16px;
  box-sizing: border-box;
  max-width: 100%;
}
</style>
