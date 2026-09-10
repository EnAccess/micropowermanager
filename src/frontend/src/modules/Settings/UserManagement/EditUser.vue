<template>
  <div>
    <widget v-if="showEditUser" :title="$tc('words.edit')" color="primary">
      <form data-vv-scope="Edit-Form">
        <div class="edit-container">
          <md-card>
            <md-card-content class="md-layout md-gutter">
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('Edit-Form.' + $tc('words.name')),
                  }"
                >
                  <label>{{ $tc("words.name") }}</label>
                  <md-input
                    disabled
                    v-model="user.name"
                    v-validate="'required|min:2|max:20'"
                    :name="$tc('words.name')"
                    id="name"
                  />
                  <md-icon>create</md-icon>
                  <span class="md-error">
                    {{ errors.first("Edit-Form." + $tc("words.name")) }}
                  </span>
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{ 'md-invalid': !phone.valid && firstStepClicked }"
                >
                  <vue-tel-input
                    id="phone"
                    :key="user.id"
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
                    v-model="user.phone"
                    @validate="validatePhone"
                    @input="onPhoneInput"
                  />
                  <md-icon>phone</md-icon>
                  <span
                    v-if="!phone.valid && firstStepClicked"
                    class="md-error"
                  >
                    invalid phone number
                  </span>
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field>
                  <label>{{ $tc("words.street") }}</label>
                  <md-input v-model="user.street" name="street" id="street" />
                  <md-icon>contacts</md-icon>
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <city-autocomplete
                  v-model="selectedCity"
                  :name="$tc('words.city')"
                  required
                  :error="errors.first('Edit-Form.' + $tc('words.city'))"
                />
              </div>
              <div
                class="md-layout-item md-size-50 md-small-size-100"
                v-if="$store.getters['auth/getPermissions'].includes('roles')"
              >
                <md-field>
                  <label for="roles">
                    Roles
                    <span style="color: red">*</span>
                  </label>
                  <md-select id="roles" v-model="selectedRoles" multiple>
                    <md-option
                      v-for="r in roleService.roles"
                      :key="r.name"
                      :value="r.name"
                    >
                      {{ r.name }}
                    </md-option>
                  </md-select>
                  <span class="md-helper-text">
                    At least one role is required
                  </span>
                </md-field>
              </div>
            </md-card-content>
            <md-card-actions>
              <md-button class="md-raised md-primary" @click="updateUser()">
                {{ $tc("words.save") }}
              </md-button>
              <md-button class="md-raised" @click="closeEditUser()">
                {{ $tc("words.close") }}
              </md-button>
            </md-card-actions>
          </md-card>
        </div>
      </form>
    </widget>
  </div>
</template>

<script>
import { notify } from "@/mixins/notify.js"
import { RoleService } from "@/services/RoleService.js"
import CityAutocomplete from "@/shared/CityAutocomplete.vue"
import Widget from "@/shared/Widget.vue"
export default {
  components: { CityAutocomplete, Widget },
  name: "EditUser",
  mixins: [notify],
  props: {
    showEditUser: {
      type: Boolean,
      default: false,
    },
    user: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      sending: false,
      selectedCity: null,
      phone: {
        valid: true,
      },
      firstStepClicked: false,
      roleService: new RoleService(),
      selectedRoles: [],
    }
  },
  mounted() {
    this.selectedCity = this.user.cityId ?? null
  },
  methods: {
    async loadRoles() {
      try {
        await this.roleService.fetchAll()
        if (this.user.id) {
          await this.roleService.fetchUserRoles(this.user.id)
          this.selectedRoles = [...this.roleService.userRoles]
        }
      } catch (e) {
        // silent
      }
    },
    async updateUser() {
      this.firstStepClicked = true
      const validation = await this.$validator.validateAll("Edit-Form")
      if (!this.phone.valid) return
      if (!validation) {
        return
      }

      // Prevent users from having no roles
      if (!this.selectedRoles || this.selectedRoles.length === 0) {
        this.$notify({
          group: "notify",
          type: "error",
          title: "Validation Error",
          text: "Users must have at least one role assigned.",
        })
        return
      }

      this.user.cityId = this.selectedCity
      // Add roles to user object to be sent with the update
      this.user.roles = this.selectedRoles
      this.$emit("updateUser", this.user)
    },
    validatePhone(phone) {
      this.phone = phone
    },
    onPhoneInput(_, phone) {
      this.phone = phone
    },
    resetPhoneValidation() {
      this.phone = { valid: true }
      this.firstStepClicked = false
    },
    closeEditUser() {
      this.$emit("editUserClosed")
    },
  },
  watch: {
    showEditUser() {
      this.selectedCity = this.user.cityId ?? null
      this.loadRoles()
      this.resetPhoneValidation()
    },
    "user.id"() {
      // Reload roles and reset validation when switching between users
      this.selectedCity = this.user.cityId ?? null
      this.loadRoles()
      this.resetPhoneValidation()
    },
  },
}
</script>

<style lang="scss" scoped>
.md-select-menu-container {
  z-index: 99999 !important;
}
</style>
