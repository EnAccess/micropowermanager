<template>
  <div class="row">
    <widget :title="$tc('words.profile')">
      <form class="md-layout" data-vv-scope="address">
        <md-card class="md-layout-item md-size-100">
          <md-card-content>
            <div class="md-layout md-gutter">
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('address.' + $tc('words.name')),
                  }"
                >
                  <label>{{ $tc("words.name") }}</label>
                  <md-input
                    v-model="userService.user.name"
                    v-validate="'required|min:2|max:20'"
                    :name="$tc('words.name')"
                    id="name"
                  />
                  <md-icon>create</md-icon>
                  <span class="md-error">
                    {{ errors.first("address." + $tc("words.name")) }}
                  </span>
                </md-field>
              </div>

              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field>
                  <label>{{ $tc("words.email") }}</label>
                  <md-input
                    readonly
                    v-model="userService.user.email"
                    name="email"
                    id="email"
                  />
                  <md-icon>sms</md-icon>
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <template>
                  <vue-tel-input
                    id="phone"
                    :key="`phone-${userService.user.id}-${refreshKey}`"
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
                    v-model="userService.user.phone"
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
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field>
                  <label>{{ $tc("words.street") }}</label>
                  <md-input v-model="userService.user.street" />
                  <md-icon>contacts</md-icon>
                </md-field>
              </div>

              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('address.' + $tc('words.city')),
                  }"
                >
                  <label for="city">
                    {{ $tc("words.city") }}
                  </label>
                  <md-select
                    v-model="selectedCity"
                    required
                    :name="$tc('words.city')"
                    id="city"
                    v-validate="'required'"
                  >
                    <md-option
                      v-for="c in cityService.cities"
                      :key="c.id"
                      :value="c.id"
                    >
                      {{ c.name }}
                    </md-option>
                  </md-select>
                  <span class="md-error">
                    {{ errors.first("address." + $tc("words.city")) }}
                  </span>
                </md-field>
              </div>
              <div class="md-layout-item md-size-100">
                <md-button
                  class="md-primary save-button"
                  @click="updateDetails()"
                >
                  {{ $tc("words.save") }}
                </md-button>
                <md-button
                  class="md-primary change-button"
                  @click="modalVisibility = true"
                >
                  {{ $tc("phrases.changePassword") }}
                </md-button>
              </div>
            </div>
          </md-card-content>
        </md-card>
        <md-progress-bar md-mode="indeterminate" v-if="sending" />
      </form>
    </widget>

    <md-dialog :md-active.sync="modalVisibility">
      <md-dialog-title>
        {{ $tc("phrases.changePassword") }}
      </md-dialog-title>
      <md-dialog-content>
        <div class="password-edit-container">
          <form class="md-layout">
            <md-field
              :class="{
                'md-invalid': errors.has('changePassword'),
              }"
            >
              <label for="changePassword">
                {{ $tc("words.password") }}
              </label>
              <md-input
                type="password"
                name="changePassword"
                id="changePassword"
                v-validate="'required|min:3|max:15'"
                v-model="passwordService.user.password"
                ref="changePasswordRef"
              />
              <span class="md-error">
                {{ errors.first("changePassword") }}
              </span>
            </md-field>

            <md-field
              :class="{
                'md-invalid': errors.has('confirmChangePassword'),
              }"
            >
              <label for="confirmChangePassword">
                {{ $tc("phrases.confirmPassword") }}
              </label>
              <md-input
                type="password"
                name="confirmChangePassword"
                id="confirmChangePassword"
                v-model="passwordService.user.confirmPassword"
                v-validate="'required|confirmed:changePasswordRef|min:3|max:128'"
              />
              <span class="md-error">
                {{ errors.first("confirmChangePassword") }}
              </span>
            </md-field>

            <md-progress-bar md-mode="indeterminate" v-if="sending" />
          </form>
        </div>
      </md-dialog-content>

      <md-dialog-actions>
        <md-button class="md-raised md-primary" @click="changePassword">
          {{ $tc("words.save") }}
        </md-button>
        <md-button @click="modalVisibility = false">
          {{ $tc("words.close") }}
        </md-button>
      </md-dialog-actions>
    </md-dialog>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { UserService } from "@/services/UserService"
import { CityService } from "@/services/CityService"
import { UserPasswordService } from "@/services/UserPasswordService"
import { notify } from "@/mixins/notify"

export default {
  name: "Profile",
  mixins: [notify],
  components: { Widget },
  data() {
    return {
      sending: false,
      modalVisibility: false,
      selectedCity: "",
      firstStepClicked: false,
      refreshKey: 0,
      userService: new UserService(),
      cityService: new CityService(),
      passwordService: new UserPasswordService(),
      phone: {
        valid: true,
      },
    }
  },
  computed: {
    phoneInput: {
      get() {
        return typeof this.userService.user.phone === "string"
          ? this.userService.user.phone
          : ""
      },
      set(val) {
        this.userService.user.phone = val
      },
    },
  },
  async mounted() {
    await this.getCities()
    await this.getUser()
    if (
      !this.userService.user.phone ||
      typeof this.userService.user.phone !== "string"
    ) {
      this.userService.user.phone = ""
    }
  },
  methods: {
    async getCities() {
      try {
        await this.cityService.getCities()
        this.setSelectedCity()
      } catch (error) {
        this.alertNotify("error", error.message)
      }
    },
    validatePhone(phone) {
      this.phone = phone
    },
    onPhoneInput(_, phone) {
      this.phone = phone
    },
    async getUser() {
      try {
        await this.userService.get(
          this.$store.getters["auth/authenticationService"].authenticateUser.id,
        )
        if (
          !this.userService.user.phone ||
          typeof this.userService.user.phone !== "string"
        ) {
          this.userService.user.phone = ""
        }
        this.setSelectedCity()
      } catch (error) {
        this.alertNotify("error", error.message)
      }
    },
    setSelectedCity() {
      if (
        this.userService.user.cityId !== undefined &&
        this.userService.user.cityId !== null
      ) {
        // If cities are loaded, verify the city exists in the list
        if (this.cityService.cities.length > 0) {
          const city = this.cityService.cities.find(
            (x) => x.id === this.userService.user.cityId,
          )
          this.selectedCity = city ? city.id : this.userService.user.cityId
        } else {
          // If cities not loaded yet, set directly (will be validated when cities load)
          this.selectedCity = this.userService.user.cityId
        }
      }
    },
    async updateDetails() {
      this.firstStepClicked = true
      this.sending = true
      let validation = await this.$validator.validateAll("address")
      if (!validation || !this.phone.valid) {
        this.sending = false
        return
      }
      if (this.selectedCity !== undefined) {
        this.userService.user.cityId = this.selectedCity
      }
      try {
        await this.userService.update()
        this.alertNotify("success", this.$tc("words.profile", 2))
        // Refresh user data after successful update
        await this.getUser()
      } catch (error) {
        this.alertNotify("error", error.message)
      }
      this.sending = false
    },
    async changePassword() {
      this.sending = true
      let validation = await this.$validator.validateAll()
      if (!validation) {
        this.sending = false
        return
      }
      try {
        await this.passwordService.update(this.userService.user.id)
        this.alertNotify("success", this.$tc("words.profile", 2))
        this.closeModal()
      } catch (error) {
        this.alertNotify("error", error)
        this.closeModal()
      }
      this.sending = false
    },
    closeModal() {
      this.modalVisibility = false
      // Clear validation errors when closing modal
      this.$validator.reset()
    },
  },
}
</script>

<style scoped>
.save-button {
  background-color: #325932 !important;
  color: #fefefe !important;
  float: right;
}
.change-button {
  background-color: #4f4e94 !important;
  color: #fefefe !important;
  float: right;
}
.password-edit-container {
  padding: 1rem;
}
</style>
