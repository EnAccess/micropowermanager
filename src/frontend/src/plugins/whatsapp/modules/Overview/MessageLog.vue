<template>
  <div>
    <md-progress-bar md-mode="indeterminate" v-if="loading" />
    <table class="log">
      <thead>
        <tr>
          <th>Time</th>
          <th>Recipient</th>
          <th>WhatsApp status</th>
          <th>Template</th>
          <th>MPM SMS status</th>
          <th>Error</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="m in messages" :key="m.id">
          <td>{{ m.created_at }}</td>
          <td>{{ m.receiver }}</td>
          <td>
            <span :class="['chip', m.status]">{{ m.status }}</span>
          </td>
          <td>{{ m.template_name || "-" }}</td>
          <td>{{ smsStatusLabel(m.sms_status) }}</td>
          <td>{{ m.error_message || "" }}</td>
        </tr>
        <tr v-if="!loading && messages.length === 0">
          <td colspan="6">No WhatsApp messages yet.</td>
        </tr>
      </tbody>
    </table>
    <div class="pager">
      <md-button :disabled="page <= 1" @click="go(page - 1)">Previous</md-button>
      <span>Page {{ page }} of {{ lastPage }}</span>
      <md-button :disabled="page >= lastPage" @click="go(page + 1)">Next</md-button>
      <md-button @click="load">Refresh</md-button>
    </div>
  </div>
</template>

<script>
import { WhatsappService } from "../../services/WhatsappService.js"

import { notify } from "@/mixins/notify.js"

export default {
  name: "MessageLog",
  mixins: [notify],
  data() {
    return {
      service: new WhatsappService(),
      messages: [],
      page: 1,
      lastPage: 1,
      loading: false,
    }
  },
  mounted() {
    this.load()
  },
  methods: {
    smsStatusLabel(status) {
      const labels = { "-1": "failed", 0: "stored", 1: "sent", 2: "delivered" }
      return labels[status] !== undefined ? labels[status] : "-"
    },
    async go(page) {
      this.page = page
      await this.load()
    },
    async load() {
      try {
        this.loading = true
        const result = await this.service.getMessages(this.page)
        this.messages = result.data
        this.lastPage = result.last_page || 1
      } catch (e) {
        this.alertNotify("error", "Could not load the message log")
      }
      this.loading = false
    },
  },
}
</script>

<style lang="scss" scoped>
.log {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
.log th,
.log td {
  text-align: left;
  padding: 8px;
  border-bottom: 1px solid #eee;
}
.chip {
  padding: 2px 8px;
  border-radius: 10px;
  color: #fff;
  background: #888;
}
.chip.sent {
  background: #2e7d32;
}
.chip.fallback_sms {
  background: #ef6c00;
}
.chip.failed {
  background: #c62828;
}
.pager {
  margin-top: 1rem;
}
</style>
