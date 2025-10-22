<template>
  <div>
    <widget v-if="showEditUser" :title="$tc('words.edit')" color="green">
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
                <md-field
                  :class="{
                    'md-invalid': errors.has('Edit-Form.' + $tc('words.city')),
                  }"
                >
                  <label for="city">
                    {{ $tc("words.city") }}
                  </label>
                  <md-select
                    v-model="selectedCity"
                    :name="$tc('words.city')"
                    id="city"
                    v-validate="'required'"
                  >
                    <md-option v-for="c in cities" :key="c.id" :value="c.id">
                      {{ c.name }}
                    </md-option>
                  </md-select>
                  <span class="md-error">
                    {{ errors.first("Edit-Form." + $tc("words.city")) }}
                  </span>
                </md-field>
              </div>
              <div
                class="md-layout-item md-size-50 md-small-size-100"
                v-if="
                  $store.getters['auth/getPermissions'].includes('roles.manage')
                "
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
import Widget from "@/shared/Widget.vue"
import { notify } from "@/mixins/notify"
import { RoleService } from "@/services/RoleService"
export default {
  components: { Widget },
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
    cities: {
      type: Array,
      required: true,
    },
  },
  data() {
    return {
      sending: false,
      selectedCity: 0,
      phone: {
        valid: true,
      },
      firstStepClicked: false,
      roleService: new RoleService(),
      selectedRoles: [],
    }
  },
  mounted() {
    this.setSelectedCity()
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
      // Save role changes first, then emit update
      const current = new Set(this.roleService.userRoles)
      const next = new Set(this.selectedRoles)
      // assign newly added
      for (const role of next) {
        if (!current.has(role)) {
          await this.roleService.assignToUser(role, this.user.id)
        }
      }
      // remove deleted
      for (const role of current) {
        if (!next.has(role)) {
          await this.roleService.removeFromUser(role, this.user.id)
        }
      }
      this.$emit("updateUser", this.user)
    },
    setSelectedCity() {
      if (this.user.cityId) {
        this.selectedCity = this.cities
          .filter((x) => x.id === this.user.cityId)
          .map((x) => x.id)[0]
      }
    },
    validatePhone(phone) {
      this.phone = phone
    },
    onPhoneInput(_, phone) {
      this.phone = phone
    },
    closeEditUser() {
      this.$emit("editUserClosed")
    },
  },
  watch: {
    showEditUser() {
      this.setSelectedCity()
      this.loadRoles()
    },
  },
}
</script>

<style lang="scss" scoped>
.md-select-menu-container {
  z-index: 99999 !important;
}
</style>
