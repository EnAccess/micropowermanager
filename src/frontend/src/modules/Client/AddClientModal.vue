<template>
  <div>
    <md-dialog
      :md-active.sync="showAddClient"
      style="max-width: 60rem; margin: auto"
    >
      <md-dialog-title>
        <div class="divider-title">
          {{ $tc("phrases.addCustomer") }}
        </div>
      </md-dialog-title>

      <md-dialog-content
        style="overflow-y: auto"
        class="md-layout-item md-size-100"
      >
        <div v-if="loading">
          <loader />
        </div>
        <div v-else class="md-layout md-gutter">
          <div class="md-layout-item md-size-100">
            <form class="md-layout md-gutter" data-vv-scope="customer-add-form">
              <div class="md-layout-item md-size-100">
                <div class="divider-title">
                  {{ $tc("phrases.personalInformation") }}
                </div>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field>
                  <label for="title">
                    {{ $tc("words.title") }}
                  </label>
                  <md-input
                    type="text"
                    name="title"
                    id="title"
                    v-model="personService.person.title"
                  />
                  <span class="md-error">
                    {{ errors.first("customer-add-form.title") }}
                  </span>
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('customer-add-form.name'),
                  }"
                >
                  <label for="name">
                    {{ $tc("words.name") }}
                  </label>
                  <md-input
                    type="text"
                    name="name"
                    id="name"
                    v-validate="'required|min:2'"
                    v-model="personService.person.name"
                  />
                  <span class="md-error">
                    {{ errors.first("customer-add-form.name") }}
                  </span>
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('customer-add-form.surname'),
                  }"
                >
                  <label for="surname">
                    {{ $tc("words.surname") }}
                  </label>
                  <md-input
                    type="text"
                    name="surname"
                    id="surname"
                    v-model="personService.person.surname"
                    v-validate="'required|min:2'"
                  />
                  <span class="md-error">
                    {{ errors.first("customer-add-form.surname") }}
                  </span>
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-datepicker
                  name="birthDate"
                  md-immediately
                  v-model="personService.person.birthDate"
                  :md-close-on-blur="false"
                >
                  <label for="birth-date">{{ $tc("words.birthday") }} :</label>
                </md-datepicker>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field>
                  <label for="gender">{{ $tc("words.gender") }} :</label>
                  <md-select
                    name="gender"
                    id="gender"
                    v-model="personService.person.gender"
                  >
                    <md-option value="male">
                      {{ $tc("words.male") }}
                    </md-option>
                    <md-option value="female">
                      {{ $tc("words.female") }}
                    </md-option>
                  </md-select>
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field>
                  <label for="education">
                    {{ $tc("words.education") }}
                  </label>
                  <md-input
                    type="text"
                    name="education"
                    id="education"
                    v-model="personService.person.education"
                  />
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('customer-add-form.email'),
                  }"
                >
                  <label for="email">
                    {{ $tc("words.email") }}
                  </label>
                  <md-input
                    type="text"
                    name="email"
                    v-validate="'email'"
                    id="email"
                    v-model="personService.person.address.email"
                  />
                  <span class="md-error">
                    {{ errors.first("customer-add-form.email") }}
                  </span>
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <template>
                  <vue-tel-input
                    id="phone"
                    name="phone"
                    ref="phoneInput"
                    :validCharactersOnly="true"
                    mode="international"
                    invalidMsg="invalid phone number"
                    :disabledFetchingCountry="false"
                    :disabledFormatting="false"
                    placeholder="Enter a phone number"
                    :required="true"
                    :preferredCountries="['TZ', 'CM', 'KE', 'NG', 'UG']"
                    autocomplete="off"
                    enabledCountryCode="true"
                    v-model="personService.person.address.phone"
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
              <div class="md-layout-item md-size-100">
                <div class="divider-title">
                  {{ $tc("words.address") }}
                </div>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('customer-add-form.city'),
                  }"
                >
                  <label for="city">
                    {{ $tc("words.city") }}
                  </label>
                  <md-select
                    name="city"
                    id="city"
                    v-model="selectedCityId"
                    v-validate="'required'"
                  >
                    <md-option
                      v-for="city in cityService.list"
                      :key="city.id"
                      :value="city.id"
                    >
                      {{ city.name }}
                    </md-option>
                  </md-select>
                  <span class="md-error">
                    {{ errors.first("customer-add-form.city") }}
                  </span>
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('customer-add-form.street'),
                  }"
                >
                  <label for="street">
                    {{ $tc("words.street") }}
                  </label>
                  <md-input
                    type="text"
                    id="street"
                    name="street"
                    v-model="personService.person.address.street"
                    v-validate="'required|min:5'"
                  />
                  <span class="md-error">
                    {{ errors.first("customer-add-form.street") }}
                  </span>
                </md-field>
              </div>
            </form>
          </div>
        </div>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button
          role="button"
          class="md-raised md-primary"
          :disabled="loading"
          @click="save"
        >
          {{ $tc("words.save") }}
        </md-button>
        <md-button role="button" class="md-raised" @click="cancel">
          {{ $tc("words.close") }}
        </md-button>
      </md-dialog-actions>
      <md-progress-bar md-mode="indeterminate" v-if="loading" />
    </md-dialog>
  </div>
</template>

<script>
import { notify } from "@/mixins"
import { PersonService } from "@/services/PersonService"
import { CityService } from "@/services/CityService"
import Loader from "@/shared/Loader.vue"
import moment from "moment"

export default {
  name: "AddClientModal",
  mixins: [notify],
  props: {
    showAddClient: {
      required: true,
      type: Boolean,
    },
  },
  components: { Loader },
  data() {
    return {
      personService: new PersonService(),
      cityService: new CityService(),
      loading: false,
      selectedCityId: null,
      phone: {
        valid: true,
      },
      firstStepClicked: false,
    }
  },
  beforeMount() {
    this.cityService.getCities()
  },
  methods: {
    async save() {
      this.firstStepClicked = true
      const validator = await this.$validator.validateAll("customer-add-form")
      if (!validator) return

      if (!this.phone.valid) return
      try {
        const personParams = {
          email: this.personService.person.address.email,
          name: this.personService.person.name,
          surname: this.personService.person.surname,
          phone: this.personService.person.address.phone,
          street: this.personService.person.address.street,
          cityId: this.personService.person.address.cityId,
          isPrimary: true,
          country_code: this.phone.countryCode,
          title: this.personService.person.title,
          education: this.personService.person.education,
          birthDate: moment(this.personService.person.birthDate).format(
            "YYYY-MM-DD HH:mm:ss",
          ),
          sex: this.personService.person.gender,
          isCustomer: true,
        }
        const person = await this.personService.createPerson(personParams)
        this.alertNotify(
          "success",
          this.$tc("messages.successfullyCreated", {
            item: this.$tc("words.customer", 1),
          }),
        )
        await this.$router.push(`/people/${person.id}`)
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    cancel() {
      this.$emit("hideAddCustomer")
    },
    validatePhone(phone) {
      this.phone = phone
    },
    onPhoneInput(_, phone) {
      this.phone = phone
    },
  },
  watch: {
    selectedCityId: function (val) {
      this.personService.person.address.cityId = val
    },
  },
}
</script>
<style scoped>
.divider-title {
  border-bottom: solid 1px #dedede;
  font-weight: 500;
  font-size: 1.2rem;
  margin: 0;
}
</style>
