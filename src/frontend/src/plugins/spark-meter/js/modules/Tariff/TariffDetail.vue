<template>
  <div>
    <widget
      id="tariff-detail"
      :title="title"
      :paginator="false"
      :button="false"
      color="red"
    >
      <form @submit.prevent="submitTariffForm" data-vv-scope="Tariff-Form">
        <md-card>
          <md-card-content>
            <div class="md-layout md-gutter">
              <div
                class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100"
              >
                <md-field
                  :class="{
                    'md-invalid': errors.has('Tariff-Form.name'),
                  }"
                >
                  <label for="name">Name</label>
                  <md-input
                    id="name"
                    name="name"
                    v-model="tariffService.tariff.name"
                    v-validate="'required|min:3'"
                  />
                  <span class="md-error">
                    {{ errors.first("Tariff-Form.name") }}
                  </span>
                </md-field>
              </div>
              <div
                class="md-layout-item md-xlarge-size-50 md-large-size-50 md-medium-size-50 md-small-size-50"
              >
                <md-field
                  :class="{
                    'md-invalid': errors.has('Tariff-Form.flat_price'),
                  }"
                >
                  <label for="flat_price">Flat Price</label>
                  <md-input
                    id="flat_price"
                    name="flat_price"
                    v-model="tariffService.tariff.flatPrice"
                    v-validate="'required|numeric'"
                    type="number"
                    @change="flatPriceChange()"
                  />
                  <span class="md-error">
                    {{ errors.first("Tariff-Form.flat_price") }}
                  </span>
                </md-field>
              </div>
              <div
                class="md-layout-item md-xlarge-size-50 md-large-size-50 md-medium-size-50 md-small-size-50"
              >
                <md-field
                  :class="{
                    'md-invalid': errors.has('Tariff-Form.flat_load_limit'),
                  }"
                >
                  <label for="name">Flat Load Limit</label>
                  <md-input
                    id="flat_load_limit"
                    name="flat_load_limit"
                    v-model="tariffService.tariff.flatLoadLimit"
                    v-validate="'required|numeric'"
                  />
                  <span class="md-error">
                    {{ errors.first("Tariff-Form.flat_load_limit") }}
                  </span>
                </md-field>
              </div>
              <div
                class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100"
              >
                <div class="md-layout md-gutter">
                  <div
                    class="md-layout-item md-xlarge-size-33 md-large-size-33 md-medium-size-33 md-small-size-33"
                  >
                    <md-checkbox
                      v-model="tariffService.tariff.dailyEnergyLimitEnabled"
                    >
                      Daily Energy Limit Enabled?
                    </md-checkbox>
                  </div>
                  <div
                    class="md-layout-item md-xlarge-size-33 md-large-size-33 md-medium-size-33 md-small-size-33"
                  >
                    <md-checkbox
                      v-model="tariffService.tariff.planEnabled"
                      @change="planEnabledChange($event)"
                    >
                      Plan Enabled?
                    </md-checkbox>
                  </div>
                  <div
                    class="md-layout-item md-xlarge-size-33 md-large-size-33 md-medium-size-33 md-small-size-33"
                  >
                    <md-checkbox
                      v-model="tariffService.tariff.touEnabled"
                      @change="touEnabledChange($event)"
                    >
                      Tou Enabled?
                    </md-checkbox>
                  </div>
                </div>
              </div>
              <!--Daily Limit-->
              <div
                v-if="tariffService.tariff.dailyEnergyLimitEnabled"
                class="md-layout-item md-xlarge-size-50 md-large-size-50 md-medium-size-50 md-small-size-100"
              >
                <md-field
                  :class="{
                    'md-invalid': errors.has(
                      'Tariff-Form.daily_energy_limit_value',
                    ),
                  }"
                >
                  <label for="daily_energy_limit_value">
                    Daily Energy Limit Value
                  </label>
                  <md-input
                    id="daily_energy_limit_value"
                    name="daily_energy_limit_value"
                    v-model="tariffService.tariff.dailyEnergyLimitValue"
                    v-validate="{
                      required: tariffService.tariff.dailyEnergyLimitEnabled,
                    }"
                  />
                  <span class="md-error">
                    {{ errors.first("Tariff-Form.daily_energy_limit_value") }}
                  </span>
                </md-field>
              </div>
              <div
                v-if="tariffService.tariff.dailyEnergyLimitEnabled"
                class="md-layout-item md-xlarge-size-50 md-large-size-50 md-medium-size-50 md-small-size-100"
              >
                <md-field
                  :class="{
                    'md-invalid': errors.has(
                      'Tariff-Form.daily_energy_limit_reset_hour',
                    ),
                  }"
                >
                  <label for="daily_energy_limit_reset_hour">
                    Daily Energy limit Reset Hour
                  </label>
                  <md-select
                    v-model="tariffService.tariff.dailyEnergyLimitResetHour"
                    name="daily_energy_limit_reset_hour"
                    id="daily_energy_limit_reset_hour"
                  >
                    <md-option
                      v-for="time in tariffService.times"
                      :value="time.time"
                      :key="time.id"
                    >
                      {{ time.time }}
                    </md-option>
                  </md-select>
                  <span class="md-error">
                    {{
                      errors.first("Tariff-Form.daily_energy_limit_reset_hour")
                    }}
                  </span>
                </md-field>
              </div>

              <!--Plan-->
              <div
                v-if="tariffService.tariff.planEnabled"
                class="md-layout-item md-xlarge-size-33 md-large-size-33 md-medium-size-33 md-small-size-33"
              >
                <md-field
                  :class="{
                    'md-invalid': errors.has('Tariff-Form.plan_duration'),
                  }"
                >
                  <label for="plan_duration">Plan Duration</label>
                  <md-select
                    v-model="tariffService.tariff.planDuration"
                    name="plan_duration"
                    id="plan_duration"
                  >
                    <md-option :value="'1m'">1m</md-option>
                    <md-option :value="'1d'">1d</md-option>
                  </md-select>
                  <span class="md-error">
                    {{ errors.first("Tariff-Form.plan_duration") }}
                  </span>
                </md-field>
              </div>
              <div
                v-if="tariffService.tariff.planEnabled"
                class="md-layout-item md-xlarge-size-33 md-large-size-33 md-medium-size-33 md-small-size-33"
              >
                <md-field
                  :class="{
                    'md-invalid': errors.has('Tariff-Form.plan_price'),
                  }"
                >
                  <label for="plan_price">Plan Price</label>
                  <md-input
                    id="plan_price"
                    name="plan_price"
                    v-model="tariffService.tariff.planPrice"
                    type="number"
                    v-validate="{
                      required: tariffService.tariff.planEnabled,
                    }"
                  />
                  <span class="md-error">
                    {{ errors.first("Tariff-Form.plan_price") }}
                  </span>
                </md-field>
              </div>
              <div
                v-if="tariffService.tariff.planEnabled"
                class="md-layout-item md-xlarge-size-34 md-large-size-34 md-medium-size-34 md-small-size-34"
              >
                <md-field
                  :class="{
                    'md-invalid': errors.has('Tariff-Form.plan_fixed_fee'),
                  }"
                >
                  <label for="plan_fixed_fee">Plan Fixed Fee</label>
                  <md-input
                    id="plan_fixed_fee"
                    name="plan_fixed_fee"
                    v-model="tariffService.tariff.planFixedFee"
                    type="number"
                    v-validate="{
                      required: tariffService.tariff.planEnabled,
                    }"
                  />
                  <span class="md-error">
                    {{ errors.first("Tariff-Form.plan_fixed_fee") }}
                  </span>
                </md-field>
              </div>

              <!--Time of Usages-->
              <div
                class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100"
              >
                <md-button
                  role="button"
                  :disabled="tariffService.conflicts.length > 0"
                  class="md-raised md-secondary"
                  @click="addTou()"
                >
                  <md-icon>add</md-icon>
                  Add TOU
                </md-button>
              </div>
              <div
                class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100"
                v-for="(tou, index) in tariffService.tariff.tous"
                :key="'tou' + index"
              >
                <div class="md-layout md-gutter">
                  <div
                    class="md-layout-item md-xlarge-size-20 md-large-size-20 md-medium-size-20 md-small-size-20"
                  >
                    <md-field
                      :class="{
                        'md-invalid': errors.has('Tariff-Form.start' + tou.id),
                      }"
                    >
                      <label for="name">Start</label>
                      <md-select
                        v-model="tou.start"
                        name="start"
                        id="start"
                        v-validate="{
                          required: tariffService.tariff.touEnabled,
                        }"
                        @md-selected="touSelected($event)"
                      >
                        <md-option
                          v-for="time in tariffService.times"
                          :value="time.time"
                          :key="time.id"
                        >
                          {{ time.time }}
                        </md-option>
                      </md-select>
                      <span class="md-error">
                        {{ errors.first("Tariff-Form.start" + tou.id) }}
                      </span>
                    </md-field>
                  </div>
                  <div
                    class="md-layout-item md-xlarge-size-20 md-large-size-20 md-medium-size-20 md-small-size-20"
                  >
                    <md-field
                      :class="{
                        'md-invalid': errors.has('Tariff-Form.end' + tou.id),
                      }"
                    >
                      <label for="end">End</label>
                      <md-select
                        v-model="tou.end"
                        name="end"
                        id="end"
                        v-validate="{
                          required: tariffService.tariff.touEnabled,
                        }"
                        @md-selected="touSelected($event)"
                      >
                        <md-option
                          v-for="time in tariffService.times"
                          :value="time.time"
                          :key="time.id"
                        >
                          {{ time.time }}
                        </md-option>
                      </md-select>
                      <span class="md-error">
                        {{ errors.first("Tariff-Form.end" + tou.id) }}
                      </span>
                    </md-field>
                  </div>
                  <div
                    class="md-layout-item md-xlarge-size-20 md-large-size-20 md-medium-size-20 md-small-size-20"
                  >
                    <md-field
                      :class="{
                        'md-invalid': errors.has('Tariff-Form.value'),
                      }"
                    >
                      <label for="value">Value</label>
                      <md-input
                        placeholder="% of normal tariff"
                        id="value"
                        name="value"
                        min="1"
                        v-model="tou.value"
                        v-validate="'required|decimal|min_value:1'"
                        @change="touValueChange(tou)"
                      />
                      <span class="md-error">
                        {{ errors.first("Tou-Form.value") }}
                      </span>
                    </md-field>
                  </div>
                  <div
                    class="md-layout-item md-xlarge-size-15 md-large-size-15 md-medium-size-15 md-small-size-15"
                  >
                    <md-field>
                      <label for="value">Cost</label>
                      <md-input :disabled="true" v-model="tou.cost" />
                    </md-field>
                  </div>
                  <div
                    class="md-layout-item md-xlarge-size-5 md-large-size-5 md-medium-size-5 md-small-size-5"
                    @click="removeTou(tou.id)"
                  >
                    <md-icon style="margin-top: 1.5rem; color: red">
                      cancel
                    </md-icon>
                  </div>
                </div>
              </div>
            </div>
          </md-card-content>
          <md-progress-bar md-mode="indeterminate" v-if="loading" />
          <md-card-actions>
            <md-button
              class="md-raised md-primary"
              type="submit"
              :disabled="loading"
            >
              Send Changes to Spark Meter
            </md-button>
          </md-card-actions>
        </md-card>
      </form>
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { TariffService } from "../../services/TariffService"
import { notify } from "@/mixins/notify"

export default {
  name: "TariffDetail",
  mixins: [notify],
  components: { Widget },
  data() {
    return {
      tariffService: new TariffService(),
      title: "Tariff Detail",
      tariffId: null,
      loading: false,
    }
  },
  created() {
    this.tariffId = this.$route.params.id
  },
  mounted() {
    this.getTariff()
  },
  methods: {
    async getTariff() {
      await this.tariffService.getTariff(this.tariffId)
    },
    async submitTariffForm() {
      let validator = await this.$validator.validateAll("Tariff-Form")
      if (validator) {
        try {
          this.loading = true
          await this.tariffService.updateTariff()
          this.loading = false
          this.alertNotify("success", "Tariff has updated successfully.")
          await this.tariffService.syncTariffs()
          this.$router.push({ path: "/spark-meters/sm-tariff" })
        } catch (e) {
          this.loading = false
          this.alertNotify("error", e.message)
        }
      }
    },
    touSelected(event) {
      this.tariffService.times.filter((x) => x.time === event)[0].using = true
      this.tariffService.findConflicts()
      this.addConflictErrors()
    },
    touValueChange(tou) {
      if (this.tariffService.tariff.flatPrice) {
        let price = this.tariffService.tariff.flatPrice / 100
        tou.cost = price * tou.value
      }
    },
    addConflictErrors() {
      this.$validator.errors.clear("Tariff-Form")
      for (let i = 0; i < this.tariffService.conflicts.length; i++) {
        let errorStart = {
          field: "start" + this.tariffService.conflicts[i],
          msg: "Overlaps !",
          scope: "Tariff-Form",
        }

        this.$validator.errors.add(errorStart)
        let errorStop = {
          field: "end" + this.tariffService.conflicts[i],
          msg: "Overlaps !",
          scope: "Tariff-Form",
        }
        this.$validator.errors.add(errorStop)
      }
    },
    addTou() {
      this.tariffService.addTou()
      this.addConflictErrors()
    },
    removeTou(id) {
      this.tariffService.removeTou(id)
      this.addConflictErrors()
    },
    flatPriceChange() {
      if (this.tariffService.tariff.tous) {
        let price = this.tariffService.tariff.flatPrice
        this.tariffService.tariff.tous.forEach((e) => {
          e.cost = (price * e.value) / 100
        })
      }
    },
    touEnabledChange(event) {
      if (event && this.tariffService.tariff.tous.length === 0) {
        this.addTou()
      }
    },
    planEnabledChange(event) {
      this.tariffService.planEnabledChange(event)
    },
  },
}
</script>

<style scoped></style>
