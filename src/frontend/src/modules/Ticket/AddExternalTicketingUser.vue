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
            <!-- phone -->
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
                v-model="ticketUserService.newUser.phone"
                @validate="validatePhone"
                @input="onPhoneInput"
              ></vue-tel-input>
              <span
                v-if="!phone.valid && firstStepClicked"
                style="color: red"
                class="md-error"
              >
                invalid phone number
              </span>
            </template>
          </div>
        </div>
        <md-progress-bar md-mode="indeterminate" v-if="loading" />
      </md-card-content>

      <md-card-actions>
        <md-button
          type="button"
          @click="saveUser"
          :disabled="loading"
          class="md-primary md-raised md-dense"
        >
          {{ $tc("words.save") }}
        </md-button>
        <md-button
          type="button"
          @click="showNewUser = false"
          class="md-accent md-raised md-dense"
        >
          {{ $tc("words.close") }}
        </md-button>
      </md-card-actions>
    </md-card>
  </form>
</template>

<script>
import { TicketUserService } from "@/services/TicketUserService"
import { EventBus } from "@/shared/eventbus"

export default {
  name: "AddExternalTicketingUser",
  data() {
    return {
      subscriber: "ticket-user-add-external",
      ticketUserService: new TicketUserService(),
      loading: false,
      phone: {
        valid: true,
      },
      firstStepClicked: false,
    }
  },
  mounted() {
    this.getUsers()
  },

  methods: {
    validatePhone(phone) {
      this.phone = phone
    },
    onPhoneInput(_, phone) {
      this.phone = phone
    },
    async saveUser() {
      let validator = await this.$validator.validateAll()
      this.firstStepClicked = true
      if (!this.phone.valid) return

      if (validator) {
        this.loading = true
        try {
          const userData = await this.ticketUserService.createExternalUser(
            this.ticketUserService.newUser.name,
            this.ticketUserService.newUser.phone,
          )

          if (userData.error !== undefined) {
            this.alertNotify(
              "warn",
              this.$tc("phrases.ticketUserNotify", 2, {
                tag: this.ticketUserService.newUser.tag,
              }),
            )
            this.loading = false
            return
          }
          await this.getUsers()
          this.alertNotify("success", this.$tc("phrases.ticketUserNotify", 1))
          this.loading = false
        } catch (e) {
          this.loading = false
          this.alertNotify("error", e.message)
        }
        this.ticketUserService.resetNewUser()
        EventBus.$emit("ticket.add.user.show", false)
      }
    },
  },
}
</script>

<style scoped></style>
