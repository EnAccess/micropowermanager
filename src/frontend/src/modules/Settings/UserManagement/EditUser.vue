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
                <md-field class="has-vue-tel" :class="{ 'md-invalid': !phone.valid && firstStepClicked }">
                  <label>{{ $tc('words.phone') }}</label>
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
                  <span v-if="!phone.valid && firstStepClicked" class="md-error">
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
    }
  },
  mounted() {
    this.setSelectedCity()
  },
  methods: {
    async updateUser() {
      this.firstStepClicked = true
      const validation = await this.$validator.validateAll("Edit-Form")

      if (!this.phone.valid) return
      if (!validation) {
        return
      }
      this.user.cityId = this.selectedCity
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
    },
  },
}
</script>

<style lang="scss" scoped>
.md-select-menu-container {
  z-index: 99999 !important;
}

/* Ensure vue-tel-input's flag and input are vertically centered inside md-field */
.md-field {
  /* keep relative positioning consistent with other fields */
  position: relative;
}

/* Use deep selector so styles apply to the vue-tel-input inner elements */
.md-field ::v-deep .vti,
.md-field ::v-deep .vti__wrapper,
.md-field ::v-deep .vue-tel-input {
  display: flex;
  align-items: center;
}

.md-field ::v-deep .vti__selected-flag,
.md-field ::v-deep .vti__flag {
  margin-top: 0 !important;
  align-self: center;
}

.md-field ::v-deep .vti__input {
  height: auto;
  /* tighten vertical padding so baseline matches other md-inputs */
  padding-top: 0.15rem;
  padding-bottom: 0.15rem;
}

/* Additional tweaks: make wrapper a flex row, ensure flag and its image are vertically centered
   and constrain the flag image size so it lines up with the input text baseline. */
.md-field ::v-deep .vti__wrapper,
.md-field ::v-deep .vue-tel-input {
  display: flex !important;
  flex-direction: row;
  align-items: center !important;
}

.md-field ::v-deep .vti__selected-flag,
.md-field ::v-deep .vti__flag {
  display: inline-flex !important;
  align-items: center !important;
  vertical-align: middle !important;
  margin-right: 0.5rem !important;
}

.md-field ::v-deep .vti__flag img {
  display: block !important;
  max-height: 1.6rem !important;
  width: auto !important;
}

.md-field ::v-deep input.vti__input {
  line-height: 1.6 !important;
  padding-top: 0 !important;
  padding-bottom: 0 !important;
}

/* Float the label for fields using vue-tel-input so it doesn't overlap the flag */
.has-vue-tel > label {
  transform: translateY(-1.25rem) scale(0.85);
  transform-origin: left top;
  z-index: 3;
  background: white; /* cover the flag edge */
  padding: 0 0.25rem;
  color: rgba(0, 0, 0, 0.54);
}

.has-vue-tel {
  padding-top: 0.6rem; /* make room for the floated label */
}
</style>
