<template>
  <div>
    <widget v-show="showAdd" :title="$tc('phrases.newTariff')" color="red">
      <md-card>
        <md-card-content>
          <div class="md-layout md-gutter">
            <!--Tariff-->
            <div class="md-layout-item md-size-100">
              <form class="md-layout md-gutter" data-vv-scope="Tariff-Form">
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has(
                        'Tariff-Form.' + $tc('words.name'),
                      ),
                    }"
                  >
                    <label for="tariff_name">
                      {{ $tc("words.name") }}
                    </label>
                    <md-input
                      id="tariff_name"
                      :name="$tc('words.name')"
                      v-model="tariffService.tariff.name"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first("Tariff-Form." + $tc("words.name")) }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has(
                        'Tariff-Form.' + $tc('words.price'),
                      ),
                    }"
                  >
                    <label for="kwh_price">
                      {{ $tc("phrases.newTariffLabels", 1) }}
                    </label>
                    <md-input
                      id="kwh_price"
                      :name="$tc('words.price')"
                      v-model="tariffService.tariff.price"
                      v-validate="'required|decimal:2'"
                      @change="tariffPriceChange()"
                    />
                    <span class="md-error">
                      {{ errors.first("Tariff-Form." + $tc("words.price")) }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-checkbox
                    :name="$tc('phrases.enableAccessRate')"
                    :id="$tc('phrases.enableAccessRate')"
                    v-model="hasAccessRate"
                    @change="accessRateChange($event)"
                  >
                    {{ $tc("phrases.enableAccessRate") }}?
                  </md-checkbox>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has(
                        'Tariff-Form.' + $tc('phrases.minimumPurchaseAmount'),
                      ),
                    }"
                  >
                    <label for="minimumPurchaseAmount">
                      {{ $tc("phrases.minimumPurchaseAmountDescription") }}
                    </label>
                    <md-input
                      id="minimumPurchaseAmount"
                      :name="$tc('phrases.minimumPurchaseAmount')"
                      v-model="tariffService.tariff.minimumPurchaseAmount"
                      v-validate="'required|decimal:2'"
                    />
                    <span class="md-error">
                      {{
                        errors.first(
                          "Tariff-Form." + $tc("phrases.minimumPurchaseAmount"),
                        )
                      }}
                    </span>
                  </md-field>
                </div>
              </form>
            </div>

            <!--Access-Rate-->
            <div class="md-layout-item md-size-100" v-show="hasAccessRate">
              <form
                class="md-layout md-gutter"
                data-vv-scope="Access-Rate-Form"
              >
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has(
                        'Access-Rate-Form.' + $tc('phrases.accessRatePrice'),
                      ),
                    }"
                  >
                    <label for="access_rate_price">
                      {{ $tc("phrases.accessRatePrice") }}
                    </label>
                    <md-input
                      id="access_rate_price"
                      :name="$tc('phrases.accessRatePrice')"
                      v-model="tariffService.tariff.accessRate.amount"
                      v-validate="'required|decimal:2'"
                    />
                    <span class="md-error">
                      {{
                        errors.first(
                          "Access-Rate-Form." + $tc("phrases.accessRatePrice"),
                        )
                      }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has(
                        'Access-Rate-Form.' +
                          $tc('phrases.accessRatePeriodInDays'),
                      ),
                    }"
                  >
                    <label for="ar_period">
                      {{ $tc("phrases.accessRatePeriodInDays") }}
                    </label>
                    <md-input
                      id="ar_period"
                      :name="$tc('phrases.accessRatePeriodInDays')"
                      v-model="tariffService.tariff.accessRate.period"
                      v-validate="'required|integer|min_value:1'"
                    />
                    <span class="md-error">
                      {{
                        errors.first(
                          "Access-Rate-Form." +
                            $tc("phrases.accessRatePeriodInDays"),
                        )
                      }}
                    </span>
                  </md-field>
                </div>
              </form>
            </div>

            <!--Additional-Components-->
            <div class="md-layout-item md-size-100">
              <md-button
                role="button"
                class="md-raised md-secondary"
                @click="addComponent('component')"
              >
                <md-icon>add</md-icon>
                {{ $tc("phrases.addAdditionalCostComponent") }}
              </md-button>
            </div>
            <div
              class="md-layout-item md-size-100"
              v-for="(component, index) in tariffService.tariff.components"
              :key="'component' + index"
            >
              <form class="md-layout md-gutter" data-vv-scope="Component-Form">
                <div class="md-layout-item md-size-45 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has(
                        'Component-Form.' + $tc('words.name'),
                      ),
                    }"
                  >
                    <label for="name">
                      {{ $tc("words.name") }}
                    </label>
                    <md-input
                      id="name"
                      :name="$tc('words.name')"
                      v-model="component.name"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first("Component-Form." + $tc("words.name")) }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-45 md-small-size-90">
                  <md-field
                    :class="{
                      'md-invalid': errors.has(
                        'Component-Form.' + $tc('phrases.componentPrice'),
                      ),
                    }"
                  >
                    <label for="price">
                      {{ $tc("phrases.componentPrice") }}
                    </label>
                    <md-input
                      id="price"
                      :name="$tc('phrases.componentPrice')"
                      v-model="component.price"
                      v-validate="'required|decimal:2'"
                    />
                    <span class="md-error">
                      {{
                        errors.first(
                          "Component-Form." + $tc("phrases.componentPrice"),
                        )
                      }}
                    </span>
                  </md-field>
                </div>
                <div
                  class="md-layout-item md-size-10"
                  @click="removeComponent('component', component.id)"
                >
                  <md-icon style="margin-top: 1.5rem; color: #ff0000">
                    cancel
                  </md-icon>
                </div>
              </form>
            </div>

            <!--TOUS-->
            <div class="md-layout-item md-size-100">
              <md-button
                role="button"
                :disabled="tariffService.conflicts.length > 0"
                class="md-raised md-secondary"
                @click="addComponent('usage')"
              >
                <md-icon>add</md-icon>
                {{ $tc("phrases.addTou") }}
              </md-button>
              <div
                v-show="tariffService.tariff.tous.length > 0"
                role="alert"
                class="alert alert-info"
              >
                <strong>{{ $tc("words.attention") }}!</strong>
                {{ $tc("phrases.newTariffLabels", 2) }}
              </div>
            </div>
            <div
              class="md-layout-item md-size-100"
              v-for="(tou, index) in tariffService.tariff.tous"
              :key="'tou' + index"
            >
              <form class="md-layout md-gutter" data-vv-scope="Tou-Form">
                <div class="md-layout-item md-size-20 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('Tou-Form.start' + tou.id),
                    }"
                  >
                    <label for="name">
                      {{ $tc("words.start") }}
                    </label>
                    <md-select
                      v-model="tou.start"
                      :name="$tc('words.start')"
                      id="start"
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
                      {{ errors.first("Tou-Form.start" + tou.id) }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-20 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('Tou-Form.end' + tou.id),
                    }"
                  >
                    <label for="end">
                      {{ $tc("words.end") }}
                    </label>
                    <md-select
                      v-model="tou.end"
                      name="end"
                      id="end"
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
                      {{ errors.first("Tou-Form.end" + tou.id) }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-20 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has(
                        'Tou-Form.' + $tc('words.value'),
                      ),
                    }"
                  >
                    <label for="value">
                      {{ $tc("words.value") }}
                    </label>
                    <md-input
                      placeholder="% of normal tariff"
                      id="value"
                      :name="$tc('words.value')"
                      min="1"
                      v-model="tou.value"
                      v-validate="'required|decimal|min_value:1'"
                      @change="touValueChange(tou)"
                    />
                    <span class="md-error">
                      {{ errors.first("Tou-Form." + $tc("words.value")) }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-15 md-small-size-90">
                  <md-field>
                    <label for="value">
                      {{ $tc("words.cost") }}
                    </label>
                    <md-input :disabled="true" v-model="tou.cost" />
                  </md-field>
                </div>
                <div
                  class="md-layout-item md-size-5 md-small-size-10"
                  @click="removeComponent('usage', tou.id)"
                >
                  <md-icon style="margin-top: 1.5rem; color: red">
                    cancel
                  </md-icon>
                </div>
              </form>
            </div>

            <!--Social-Tariffs-->
            <!--                        <div class="md-layout-item md-size-100">
                            <a @click="showSocialOptions()" v-if="!tariffService.socialOptions"
                               class="show-tariff-link">{{ $tc('phrases.socialTariffOptions', 1) }}</a>
                            <a @click="showSocialOptions()" v-else
                               class="show-tariff-link">{{ $tc('phrases.socialTariffOptions', 2) }}</a>
                        </div>-->
            <div class="md-layout-item md-size-100" v-show="socialOptions">
              <form class="md-layout md-gutter" data-vv-scope="Social-Form">
                <div class="md-layout-item md-size-30 md-small-size-50">
                  <h3>
                    {{ $tc("phrases.socialTariffLabels") }}
                  </h3>
                </div>
                <div class="md-layout-item md-size-20 md-small-size-50">
                  <md-field
                    :class="{
                      'md-invalid': errors.has(
                        'Social-Form.' + $tc('phrases.socialTariffLabels'),
                      ),
                    }"
                  >
                    <md-input
                      id="daily_allowance"
                      type="number"
                      min="0"
                      :name="$tc('phrases.socialTariffLabels')"
                      v-model="tariffService.tariff.socialTariff.dailyAllowance"
                      v-validate="'required|integer'"
                      class="social-input"
                    />

                    <span class="md-error">
                      {{
                        errors.first(
                          "Social-Form." + $tc("phrases.socialTariffLabels"),
                        )
                      }}
                    </span>
                    <span class="md-suffix">Wh.</span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100"></div>

                <div class="md-layout-item md-size-30 md-small-size-50">
                  <h3>
                    {{ $tc("phrases.socialTariff", 0) }}
                  </h3>
                </div>
                <div class="md-layout-item md-size-20 md-small-size-50">
                  <md-field
                    :class="{
                      'md-invalid': errors.has(
                        'Social-Form.' + $tc('phrases.socialTariff', 1),
                      ),
                    }"
                  >
                    <label for="social_price">
                      {{ $tc("phrases.socialTariff", 1) }}
                    </label>
                    <md-input
                      id="social_price"
                      :name="$tc('phrases.socialTariff', 1)"
                      min="0"
                      v-model="tariffService.tariff.socialTariff.price"
                      v-validate="'required|integer'"
                      class="social-input"
                    />
                    <span class="md-error">
                      {{
                        errors.first(
                          "Social-Form." + $tc("phrases.socialTariff", 1),
                        )
                      }}
                    </span>
                    <span class="md-suffix">
                      {{ $store.getters["settings/getMainSettings"].currency }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100"></div>

                <div class="md-layout-item md-size-30 md-small-size-50">
                  <h3>
                    {{ $tc("phrases.socialTariff", 2) }}
                  </h3>
                </div>
                <div class="md-layout-item md-size-20 md-small-size-50">
                  <md-field
                    :class="{
                      'md-invalid': errors.has(
                        'Social-Form.' + $tc('phrases.socialTariff', 2),
                      ),
                    }"
                  >
                    <md-input
                      id="initial_energy_budget"
                      type="number"
                      min="0"
                      :name="$tc('phrases.socialTariff', 2)"
                      v-model="
                        tariffService.tariff.socialTariff.initialEnergyBudget
                      "
                      v-validate="'required|integer'"
                      class="social-input"
                    />
                    <span class="md-error">
                      {{
                        errors.first(
                          "Social-Form." + $tc("phrases.socialTariff", 2),
                        )
                      }}
                    </span>
                    <span class="md-suffix">Wh.</span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100"></div>

                <div class="md-layout-item md-size-30 md-small-size-50">
                  <h3>
                    {{ $tc("phrases.socialTariffLabels", 2) }}
                  </h3>
                </div>
                <div class="md-layout-item md-size-20 md-small-size-50">
                  <md-field
                    :class="{
                      'md-invalid': errors.has(
                        'Social-Form.' + $tc('phrases.socialTariffLabels', 2),
                      ),
                    }"
                  >
                    <md-input
                      id="maximum_stacked_energy"
                      type="number"
                      min="0"
                      :name="$tc('phrases.socialTariffLabels', 2)"
                      v-model="
                        tariffService.tariff.socialTariff.maximumStackedEnergy
                      "
                      v-validate="'required|integer'"
                      class="social-input"
                    />
                    <span class="md-error">
                      {{
                        errors.first(
                          "Social-Form." + $tc("phrases.socialTariffLabels", 2),
                        )
                      }}
                    </span>
                    <span class="md-suffix">Wh.</span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100"></div>
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
            @click="saveTariff"
          >
            {{ $tc("words.save") }}
          </md-button>
          <md-button role="button" class="md-raised" @click="hide">
            {{ $tc("words.close") }}
          </md-button>
        </md-card-actions>
      </md-card>
    </widget>
  </div>
</template>

<script>
import { EventBus } from "@/shared/eventbus"
import Widget from "@/shared/Widget.vue"
import { TariffService } from "@/services/TariffService"
import { notify } from "@/mixins/notify"

export default {
  name: "Add",
  mixins: [notify],
  components: { Widget },
  data() {
    return {
      showAdd: false,
      hasAccessRate: false,
      tariffService: new TariffService(),
      socialOptions: false,
      loading: false,
    }
  },
  mounted() {
    this.tariffService.generateTimes()
    EventBus.$on("showNewTariff", this.show)
  },
  methods: {
    hide() {
      this.hasAccessRate = false
      this.showAdd = false
    },
    show() {
      this.showAdd = true
    },
    async saveTariff() {
      let validatorTariff = true
      let validatorAccessRate = true
      let validatorComponent = true
      let validatorSocial = true
      let validatorTou = true
      if (this.hasAccessRate)
        validatorAccessRate =
          await this.$validator.validateAll("Access-Rate-Form")
      if (this.socialOptions)
        validatorSocial = await this.$validator.validateAll("Social-Form")
      if (this.tariffService.tariff.components.length > 0)
        validatorComponent = await this.$validator.validateAll("Component-Form")
      if (this.tariffService.tariff.tous.length > 0)
        validatorTou = await this.$validator.validateAll("Tou-Form")
      validatorTariff = await this.$validator.validateAll("Tariff-Form")

      if (
        validatorTariff &&
        validatorAccessRate &&
        validatorComponent &&
        validatorSocial &&
        validatorTou
      ) {
        try {
          this.loading = true
          this.tariffService.setCurrency(
            this.$store.getters["settings/getMainSettings"].currency,
          )
          await this.tariffService.saveTariff("create")
          this.loading = false
          this.hide()
          EventBus.$emit("tariffAdded", this.tariff)
          this.alertNotify("success", this.$tc("phrases.newTariff", 2))
          this.tariffService.resetTariff()
        } catch (e) {
          this.tariffService.resetTariff()
          this.loading = false
          this.alertNotify("error", e.message)
        }
      }
    },
    addComponent(addedType) {
      this.tariffService.addAdditionalCostComponent(addedType)
      this.addConflictErrors()
    },
    removeComponent(addedType, id) {
      this.tariffService.removeAdditionalComponent(addedType, id)
      this.addConflictErrors()
    },
    showSocialOptions() {
      this.socialOptions = !this.socialOptions
      this.tariffService.resetSocialTariff()
    },
    accessRateChange(event) {
      if (!event) {
        this.tariffService.resetAccessRate()
      }
    },
    touSelected(event) {
      this.tariffService.times.filter((x) => x.time === event)[0].using = true
      this.tariffService.findConflicts()
      this.addConflictErrors()
    },
    addConflictErrors() {
      this.$validator.errors.clear("Tou-Form")
      for (let i = 0; i < this.tariffService.conflicts.length; i++) {
        let errorStart = {
          field: "start" + this.tariffService.conflicts[i],
          msg: "Overlaps !",
          scope: "Tou-Form",
        }

        this.$validator.errors.add(errorStart)
        let errorStop = {
          field: "end" + this.tariffService.conflicts[i],
          msg: "Overlaps !",
          scope: "Tou-Form",
        }
        this.$validator.errors.add(errorStop)
      }
    },
    touValueChange(tou) {
      if (this.tariffService.tariff.price) {
        let price = this.tariffService.tariff.price
        tou.cost = price * tou.value
      }
    },
    tariffPriceChange() {
      if (this.tariffService.tariff.tous) {
        let price = this.tariffService.tariff.price
        this.tariffService.tariff.tous.forEach((e) => {
          e.cost = price * e.value
        })
      }
    },
  },
}
</script>

<style scoped>
.show-tariff-link {
  cursor: pointer;
}

.social-input {
  text-align: right;
}

input[type="time"]::-webkit-calendar-picker-indicator {
  background: none;
}

.alert {
  padding: 15px;
  margin-bottom: 20px;
  border: 1px solid transparent;
  border-radius: 4px;
}

.alert h4 {
  margin-top: 0;
  color: inherit;
}

.alert .alert-link {
  font-weight: bold;
}

.alert > p,
.alert > ul {
  margin-bottom: 0;
}

.alert > p + p {
  margin-top: 5px;
}

.alert-dismissable,
.alert-dismissible {
  padding-right: 35px;
}

.alert-dismissable .close,
.alert-dismissible .close {
  position: relative;
  top: -2px;
  right: -21px;
  color: inherit;
}

.alert-info {
  background-color: #d9edf7;
  border-color: #bce8f1;
  color: #31708f;
}

.alert-info hr {
  border-top-color: #a6e1ec;
}

.alert-info .alert-link {
  color: #245269;
}
</style>
