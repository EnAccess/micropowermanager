<template>
  <form class="md-layout">
    <md-card class="md-layout-item md-size-100">
      <md-card-content>
        <div class="md-layout md-gutter">
          <div class="md-layout-item md-size-100">
            <md-field
              :class="{
                'md-invalid': errors.has($tc('words.name')),
              }"
            >
              <label>{{ $tc("words.name") }}</label>
              <md-input
                v-model="ticketUserService.newUser.name"
                :name="$tc('words.name')"
                id="name"
                v-validate="'required|min:3'"
              ></md-input>
              <span class="md-error">
                {{ errors.first($tc("words.name")) }}
              </span>
            </md-field>
            <template>
              <vue-tel-input
                id="phone"
                :validCharactersOnly="true"
                mode="international"
                invalidMsg="invalid phone number"
                :disabledFetchingCountry="false"
                :disabledFormatting="false"
                placeholder="Enter a phone number"
                :required="true"
                :preferredCountries="['TZ', 'CM', 'KE', 'NG', 'UG']"
                autocomplete="off"
                :name="$tc('words.phone')"
                enabledCountryCode="true"
                :value="ticketUserService.newUser.phone || ''"
                @validate="validatePhone"
                @input="onPhoneInput"
              />
              <span
                v-if="phoneObj && phoneObj.valid === false && firstStepClicked"
                style="color: red"
                class="md-error"
              >
                invalid phone number
              </span>
            </template>
          </div>
        </div>
        <md-progress-bar md-mode="indeterminate" v-if="saving" />
      </md-card-content>

      <md-card-actions>
        <md-button
          type="button"
          @click="saveUser"
          :disabled="saving"
          class="md-primary md-raised md-dense"
        >
          {{ $tc("words.save") }}
        </md-button>
        <md-button
          type="button"
          @click="$emit('cancel')"
          class="md-accent md-raised md-dense"
        >
          {{ $tc("words.close") }}
        </md-button>
      </md-card-actions>
    </md-card>
  </form>
</template>

<script>
import { notify } from "@/mixins/notify"
import { TicketUserService } from "@/services/TicketUserService"
import { EventBus } from "@/shared/eventbus"

export default {
  name: "AddExternalTicketingUser",
  mixins: [notify],
  data() {
    return {
      ticketUserService: new TicketUserService(),
      phoneObj: { valid: null },
      firstStepClicked: false,
      saving: false,
    }
  },
  mounted() {},
  methods: {
    validatePhone(phone) {
      this.phoneObj = phone || { valid: false }
    },
    onPhoneInput(value) {
      this.ticketUserService.newUser.phone = value == null ? "" : String(value)
    },
    async saveUser() {
      this.firstStepClicked = true
      if (this.phoneObj && this.phoneObj.valid === false) {
        this.alertNotify(
          "error",
          this.$tc("validation.invalidPhone") || "Invalid phone",
        )
        return
      }

      try {
        this.saving = true
        const name = this.ticketUserService.newUser.name
        const phone = this.ticketUserService.newUser.phone
        const created = await this.ticketUserService.createExternalUser(
          name,
          phone,
        )

        if (created && created.statusCode) {
          this.alertNotify("error", created.message || "Failed to create user")
          return
        }

        if (created && (created.id || created.success)) {
          this.$emit("created", created)
          this.alertNotify(
            "success",
            "External ticketing user created successfully",
          )
          EventBus.$emit("ticket.add.user", false)
          this.ticketUserService.resetNewUser()
          this.$validator.reset()
          this.firstStepClicked = false
          this.phoneObj = { valid: null }
          this.$emit("cancel")
        } else {
          this.alertNotify("error", "User created but response invalid")
        }
      } catch (err) {
        const msg =
          (err.response && err.response.data && err.response.data.message) ||
          err.message ||
          "Failed to create user"
        this.alertNotify("error", msg)
      } finally {
        this.saving = false
      }
    },
  },
}
</script>

<style scoped></style>
