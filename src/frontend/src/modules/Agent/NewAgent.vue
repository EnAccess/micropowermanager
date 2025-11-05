<template>
  <div>
    <widget v-show="addAgent" :title="$tc('phrases.newAgent')" color="red">
      <md-card>
        <md-card-content>
          <div class="md-layout md-gutter">
            <div
              class="md-layout-item md-large-size-100 md-medium-size-100 md-small-size-100"
            >
              <form class="md-layout md-gutter" ref="agentForm">
                <!--name-->
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('words.name')),
                    }"
                  >
                    <label for="name">
                      {{ $tc("words.name") }}
                    </label>
                    <md-input
                      id="name"
                      :name="$tc('words.name')"
                      v-model="agentService.agent.name"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first($tc("words.name")) }}
                    </span>
                  </md-field>
                </div>

                <!--surname-->
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('words.surname')),
                    }"
                  >
                    <label for="surname">
                      {{ $tc("words.surname") }}
                    </label>
                    <md-input
                      id="surname"
                      :name="$tc('words.surname')"
                      v-model="agentService.agent.surname"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first($tc("words.surname")) }}
                    </span>
                  </md-field>
                </div>

                <!--minigrid-->
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('words.miniGrid')),
                    }"
                  >
                    <label>
                      {{ $tc("words.miniGrid") }}
                    </label>
                    <md-select
                      v-model="agentService.agent.miniGridId"
                      :name="$tc('words.miniGrid')"
                      id="miniGridName"
                      v-validate="'required'"
                    >
                      <md-option
                        v-for="mg in miniGrids"
                        :value="mg.id"
                        :key="mg.id"
                      >
                        {{ mg.name }}
                      </md-option>
                    </md-select>
                    <span class="md-error">
                      {{ errors.first($tc("words.miniGrid")) }}
                    </span>
                  </md-field>
                </div>

                <!--phone-->
                <div class="md-layout-item md-size-50 md-small-size-100">
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
                      v-model="agentService.agent.phone"
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

                <!--email-->
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('words.email')),
                    }"
                  >
                    <label for="email">
                      {{ $tc("words.email") }}
                    </label>
                    <md-input
                      id="email"
                      :name="$tc('words.email')"
                      v-model="agentService.agent.email"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first($tc("words.email")) }}
                    </span>
                  </md-field>
                </div>

                <div class="md-layout-item md-size-50 md-small-size-100">
                  <label>{{ $tc("words.birthday") }}</label>
                  <md-datepicker
                    id="birth-date"
                    name="birthDate"
                    md-immediately
                    v-model="agentService.agent.birthday"
                    :md-close-on-blur="false"
                  ></md-datepicker>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('words.gender')),
                    }"
                  >
                    <label for="gender">{{ $tc("words.gender") }} :</label>
                    <md-select
                      :name="$tc('words.gender')"
                      id="gender"
                      v-model="agentService.agent.gender"
                      v-validate="'required'"
                    >
                      <md-option
                        disabled
                        v-if="agentService.agent.gender == null"
                      >
                        -- {{ $tc("words.select") }} --
                      </md-option>
                      <md-option value="male">
                        {{ $tc("words.male") }}
                      </md-option>
                      <md-option value=" female">
                        {{ $tc("words.female") }}
                      </md-option>
                    </md-select>

                    <span class="md-error">
                      {{ errors.first($tc("words.gender")) }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('phrases.commissionType')),
                    }"
                  >
                    <label for="commission">
                      {{ $tc("phrases.commissionType") }}:
                    </label>
                    <md-select
                      :name="$tc('phrases.commissionType')"
                      id="commission"
                      v-validate="'required'"
                      v-model="agentService.agent.commissionTypeId"
                    >
                      <md-option
                        v-for="commission in agentCommissions"
                        :value="commission.id"
                        :key="commission.id"
                      >
                        {{ commission.name }}
                      </md-option>
                    </md-select>
                    <span class="md-error">
                      {{ errors.first($tc("phrases.commissionType")) }}
                    </span>
                  </md-field>
                </div>
                <!--password-->
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('words.password')),
                    }"
                  >
                    <label for="password">
                      {{ $tc("words.password") }}
                    </label>
                    <md-input
                      id="password"
                      :name="$tc('words.password')"
                      v-model="agentService.agent.password"
                      v-validate="'required|min:3|max:15'"
                      ref="passwordRef"
                      type="password"
                    />
                    <span class="md-error">
                      {{ errors.first($tc("words.password")) }}
                    </span>
                  </md-field>
                </div>

                <!--confirmPassword-->
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('phrases.confirmPassword')),
                    }"
                  >
                    <label for="confirmPassword">
                      {{ $tc("phrases.confirmPassword") }}
                    </label>
                    <md-input
                      id="confirmPassword"
                      :name="$tc('phrases.confirmPassword')"
                      v-model="confirmPassword"
                      v-validate="'required|confirmed:passwordRef'"
                      type="password"
                    />
                    <span class="md-error">
                      {{ errors.first($tc("phrases.confirmPassword")) }}
                    </span>
                  </md-field>
                </div>
              </form>
            </div>
          </div>
          <md-progress-bar md-mode="indeterminate" v-show="loading" />
        </md-card-content>
        <md-card-actions>
          <md-button
            role="button"
            class="md-raised md-primary"
            :disabled="loading"
            @click="saveAgent"
          >
            {{ $tc("words.save") }}
          </md-button>
          <md-button role="button" class="md-raised" @click="hide">
            {{ $tc("words.close") }}
          </md-button>
        </md-card-actions>
      </md-card>
    </widget>
    <redirection-modal
      :redirection-url="redirectionUrl"
      :imperative-item="imperativeItem"
      :dialog-active="redirectDialogActive"
    />
  </div>
</template>
<script>
import Widget from "@/shared/Widget.vue"
import { AgentService } from "@/services/AgentService"
import { MiniGridService } from "@/services/MiniGridService"
import CountryService from "../../services/CountryService"
import { EventBus } from "@/shared/eventbus"
import { AgentCommissionService } from "@/services/AgentCommissionService"
import RedirectionModal from "../../shared/RedirectionModal"
import { notify } from "@/mixins/notify"

export default {
  mixins: [notify],
  name: "AddAgent",
  components: { Widget, RedirectionModal },
  props: {
    addAgent: {
      default: false,
      type: Boolean,
    },
  },
  data() {
    return {
      agentService: new AgentService(),
      miniGridService: new MiniGridService(),
      countryService: new CountryService(),
      agentCommissionService: new AgentCommissionService(),
      agentCommissions: [],
      users: [],
      selectedUser: null,
      selectedMiniGridId: "",
      miniGrids: [],
      countries: [],
      confirmPassword: null,
      loading: false,
      redirectionUrl: "/locations/add-mini-grid",
      imperativeItem: "Mini-Grid",
      redirectDialogActive: false,
      phone: {
        valid: true,
      },
      firstStepClicked: false,
    }
  },

  mounted() {
    this.getMiniGrids()
    this.getCountries()
    this.getAgentCommissions()
  },
  methods: {
    async getMiniGrids() {
      try {
        this.miniGrids = await this.miniGridService.getMiniGrids()
        if (this.miniGrids.length < 0) {
          this.redirectDialogActive = true
        }
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getCountries() {
      try {
        this.countries = await this.countryService.getCountries()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getAgentCommissions() {
      try {
        this.agentCommissions =
          await this.agentCommissionService.getAgentCommissions()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async selectMiniGrid(miniGridId) {
      this.selectedMiniGridId = miniGridId
    },
    async selectNationality(miniGridId) {
      this.selectedMiniGridId = miniGridId
    },
    normalizeDateToISO(date) {
      if (!date) return null
      if (date instanceof Date && !isNaN(date.getTime())) {
        const year = date.getFullYear()
        const month = String(date.getMonth() + 1).padStart(2, "0")
        const day = String(date.getDate()).padStart(2, "0")
        return `${year}-${month}-${day}`
      }
      if (typeof date === "string") {
        const s = date.trim()
        if (!s) return null
        if (s.toLowerCase().includes("invalid")) return null
        const parsed = new Date(s)
        if (!isNaN(parsed.getTime())) {
          const year = parsed.getFullYear()
          const month = String(parsed.getMonth() + 1).padStart(2, "0")
          const day = String(parsed.getDate()).padStart(2, "0")
          return `${year}-${month}-${day}`
        }
      }
      return null
    },

    async saveAgent() {
      this.firstStepClicked = true
      let validator = await this.$validator.validateAll()
      if (!this.phone.valid) return
      if (!validator) return

      this.loading = true
      try {
        const normalized = this.normalizeDateToISO(
          this.agentService.agent.birthday,
        )
        this.agentService.agent.birth_date = normalized
        if ("birthday" in this.agentService.agent) {
          delete this.agentService.agent.birthday
        }

        const created = await this.agentService.createAgent()
        this.$emit("agent-added", created)
        EventBus.$emit("agentCreated", created)

        this.alertNotify("success", this.$tc("phrases.agentCreatedSuccess"))
        this.$refs["agentForm"].reset()
        this.confirmPassword = null
        this.agentService.agent = {}
        this.hide()
      } catch (e) {
        this.alertNotify("error", e.message || e)
      } finally {
        this.loading = false
      }
    },
    validatePhone(phone) {
      this.phone = phone
    },
    onPhoneInput(_, phone) {
      this.phone = phone
    },
    hide() {
      EventBus.$emit("closed")
    },
  },
}
</script>
<style scoped></style>
