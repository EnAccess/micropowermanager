<template>
  <div>
    <md-tabs md-alignment="left" @md-changed="onTabChanged">
      <md-tab id="templates" :md-label="$t('phrases.whatsappTemplatesTitle')" />
      <md-tab
        id="notifications"
        :md-label="$t('phrases.whatsappTemplatesNotifications')"
      />
    </md-tabs>

    <widget
      v-if="tab === 'templates'"
      :key="widgetKey"
      :title="$t('phrases.whatsappTemplatesTitle')"
      color="primary"
      :paginator="paginator"
      :subscriber="subscriber"
      :show_per_page="true"
      :max_per_page="100"
      :show-refresh-button="true"
      @refreshButtonClicked="refresh"
    >
      <p class="md-caption hint">
        {{ $t("phrases.whatsappTemplatesHint") }}
      </p>
      <md-progress-bar md-mode="indeterminate" v-if="loading" />
      <md-table v-model="templates" md-card>
        <md-table-row slot="md-table-row" slot-scope="{ item }">
          <md-table-cell :md-label="$tc('words.name')">
            <code>{{ item.name }}</code>
          </md-table-cell>
          <md-table-cell :md-label="$t('phrases.whatsappTemplatesLanguage')">
            {{ item.language }}
          </md-table-cell>
          <md-table-cell :md-label="$t('phrases.whatsappTemplatesCategory')">
            {{ item.category }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.status')">
            <span :class="`status status--${statusTone(item.status)}`">
              {{ item.status }}
            </span>
          </md-table-cell>
          <md-table-cell :md-label="$t('phrases.whatsappTemplatesUsedFor')">
            {{
              item.used_for
                ? $t(`phrases.whatsappNotification${item.used_for}`)
                : $t("phrases.whatsappTemplatesManualMessages")
            }}
          </md-table-cell>
          <md-table-cell
            class="details"
            :md-label="$t('phrases.whatsappTemplatesDetails')"
          >
            <div class="body">{{ item.body }}</div>
            <div v-if="item.rejected_reason" class="problem">
              <strong>{{ rejectionExplanation(item.rejected_reason) }}</strong>
            </div>
            <div v-if="item.rejection_detail" class="problem">
              {{ $t("phrases.whatsappTemplatesMetaSays") }}
              {{ item.rejection_detail }}
            </div>
            <div v-if="item.rejection_recommendation">
              {{ $t("phrases.whatsappTemplatesMetaRecommends") }}
              {{ item.rejection_recommendation }}
            </div>
            <div v-if="item.status_detail" class="problem">
              {{ item.status_detail }}
            </div>
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>

    <widget
      v-else
      :title="$t('phrases.whatsappTemplatesNotifications')"
      color="primary"
    >
      <p class="md-caption hint">
        {{ $t("phrases.whatsappTemplatesNotificationsHint") }}
      </p>
      <md-progress-bar md-mode="indeterminate" v-if="notificationsLoading" />
      <md-table v-else>
        <md-table-row>
          <md-table-head>
            {{ $t("phrases.whatsappTemplatesNotification") }}
          </md-table-head>
          <md-table-head>
            {{ $t("phrases.whatsappTemplatesTemplateName") }}
          </md-table-head>
          <md-table-head>
            {{ $t("phrases.whatsappTemplatesParameters") }}
          </md-table-head>
          <md-table-head>{{ $tc("words.status") }}</md-table-head>
        </md-table-row>
        <md-table-row
          v-for="notification in templateService.notifications"
          :key="notification.reference"
        >
          <md-table-cell>
            {{ $t(`phrases.whatsappNotification${notification.reference}`) }}
          </md-table-cell>
          <md-table-cell>
            <code>{{ notification.template_name }}</code>
          </md-table-cell>
          <md-table-cell>
            {{ notification.parameters.join(", ") }}
          </md-table-cell>
          <md-table-cell>
            <span v-if="notification.problem === null" class="ready">
              <md-icon>check_circle</md-icon>
              {{ $t("phrases.whatsappTemplatesReady") }}
            </span>
            <span v-else class="problem">
              <md-icon>sms</md-icon>
              {{ $t("phrases.whatsappTemplatesGoesBySms") }}
              {{ notification.problem }}
            </span>
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
  </div>
</template>

<script>
import { WhatsappTemplateService } from "../../services/WhatsappTemplateService.js"

import { notify } from "@/mixins/notify.js"
import { EventBus } from "@/shared/eventbus.js"
import Widget from "@/shared/Widget.vue"

// Meta's reasons for rejecting a template; anything newer falls back to a generic explanation.
const REJECTION_REASONS = {
  ABUSIVE_CONTENT: "phrases.whatsappRejectionAbusiveContent",
  INCORRECT_CATEGORY: "phrases.whatsappRejectionIncorrectCategory",
  INVALID_FORMAT: "phrases.whatsappRejectionInvalidFormat",
  PROMOTIONAL: "phrases.whatsappRejectionPromotional",
  SCAM: "phrases.whatsappRejectionScam",
  TAG_CONTENT_MISMATCH: "phrases.whatsappRejectionTagContentMismatch",
}

export default {
  name: "Templates",
  components: { Widget },
  mixins: [notify],
  data() {
    const templateService = new WhatsappTemplateService()
    return {
      templateService,
      paginator: templateService.templatesPaginator(),
      subscriber: "whatsapp-templates",
      templates: [],
      tab: "templates",
      loading: false,
      notificationsLoading: false,
      reloadCount: 0,
    }
  },
  computed: {
    // Widget re-mounts its Paginate on key change, which reloads the list from page 1.
    widgetKey() {
      return `templates-${this.reloadCount}`
    },
  },
  // Opening the page asks Meta, so templates changed while no webhook was subscribed still show up.
  async mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
    await this.refresh()
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
  },
  methods: {
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) return
      this.templates = data
      EventBus.$emit("widgetContentLoaded", this.subscriber, data.length)
    },
    async refresh() {
      this.loading = true
      try {
        await this.templateService.refreshTemplates()
      } catch (e) {
        this.alertNotify("error", e.message)
      } finally {
        this.loading = false
        this.reloadCount++
      }
    },
    async onTabChanged(tab) {
      this.tab = tab
      if (tab !== "notifications") return
      this.notificationsLoading = true
      try {
        await this.templateService.getNotifications()
      } catch (e) {
        this.alertNotify("error", e.message)
      } finally {
        this.notificationsLoading = false
      }
    },
    statusTone(status) {
      if (status === "APPROVED") return "ok"
      if (status === "PENDING" || status === "IN_APPEAL") return "waiting"
      return "problem"
    },
    rejectionExplanation(reason) {
      return this.$t(
        REJECTION_REASONS[reason] ?? "phrases.whatsappRejectionOther",
      )
    },
  },
}
</script>

<style lang="scss" scoped>
.hint {
  margin: 0.5rem 1rem;
}

.details {
  max-width: 420px;

  div + div {
    margin-top: 0.4rem;
  }
}

.body {
  white-space: pre-line;
  color: rgba(0, 0, 0, 0.6);
}

.ready {
  color: #2e7d32;
}

.problem {
  color: #b26a00;
}

.status {
  font-weight: 500;
}

.status--ok {
  color: #2e7d32;
}

.status--waiting {
  color: rgba(0, 0, 0, 0.6);
}

.status--problem {
  color: #c62828;
}
</style>
