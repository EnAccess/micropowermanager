<template>
  <div>
    <p class="help">
      Only customers listed here receive WhatsApp messages. Record a customer
      only after they have agreed to be contacted on WhatsApp. Everyone else
      gets the fallback SMS.
    </p>
    <div class="add">
      <md-field>
        <label for="optInPhone">Customer phone (international format)</label>
        <md-input id="optInPhone" name="optInPhone" v-model="phone" />
      </md-field>
      <md-button class="md-raised md-primary" @click="add">
        Record opt-in
      </md-button>
    </div>

    <table class="log">
      <thead>
        <tr>
          <th>Phone</th>
          <th>Opted in</th>
          <th>Source</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="o in optIns" :key="o.id">
          <td>{{ o.phone }}</td>
          <td>{{ o.opted_in_at }}</td>
          <td>{{ o.source }}</td>
          <td>{{ o.revoked_at ? "revoked " + o.revoked_at : "active" }}</td>
          <td>
            <md-button
              v-if="!o.revoked_at"
              class="md-accent"
              @click="revoke(o.id)"
            >
              Revoke
            </md-button>
          </td>
        </tr>
        <tr v-if="optIns.length === 0">
          <td colspan="5">No opt-ins recorded yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import { WhatsappService } from "../../services/WhatsappService.js"

import { notify } from "@/mixins/notify.js"

export default {
  name: "OptIns",
  mixins: [notify],
  data() {
    return {
      service: new WhatsappService(),
      optIns: [],
      phone: "",
    }
  },
  mounted() {
    this.load()
  },
  methods: {
    async load() {
      try {
        const result = await this.service.getOptIns()
        this.optIns = result.data
      } catch (e) {
        this.alertNotify("error", "Could not load opt-ins")
      }
    },
    async add() {
      if (!this.phone) {
        this.alertNotify("error", "Enter a phone number first")
        return
      }
      try {
        await this.service.addOptIn(this.phone)
        this.phone = ""
        this.alertNotify("success", "Opt-in recorded")
        await this.load()
      } catch (e) {
        this.alertNotify("error", "Could not record the opt-in")
      }
    },
    async revoke(id) {
      try {
        await this.service.revokeOptIn(id)
        this.alertNotify("success", "Opt-in revoked")
        await this.load()
      } catch (e) {
        this.alertNotify("error", "Could not revoke the opt-in")
      }
    },
  },
}
</script>

<style lang="scss" scoped>
.help {
  color: #666;
  font-size: 13px;
}
.add {
  display: flex;
  gap: 1rem;
  align-items: center;
}
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
</style>
