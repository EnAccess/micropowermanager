<template>
  <widget>
    <div class="meter-overview-card">
      <div class="md-subheading">{{ $tc("words.basic") }}</div>
      <div
        class="meter-overview-detail"
        v-if="meter !== null && meter.loaded === true"
      >
        <div class="md-layout">
          <div class="md-layout-item">{{ $tc("words.register", 2) }}:</div>
          <div class="md-layout-item">
            {{ timeForTimeZone(meter.registered) }}
          </div>
        </div>
        <div class="md-layout">
          <div class="md-layout-item">{{ $tc("words.owner") }}:</div>
          <div class="md-layout">
            <div class="md-layout-item">
              <div v-if="!showOwnerEdit">
                <router-link :to="`/people/${meter.owner.id}`">
                  {{ meter.owner.name }}
                  {{ meter.owner.surname }}
                </router-link>
                <span style="cursor: pointer" @click="showOwnerEdit = true">
                  <md-icon>edit</md-icon>
                </span>
              </div>

              <div class="md-layout-item" v-if="showOwnerEdit">
                <md-autocomplete
                  v-model="customerSearchTerm"
                  :md-options="searchNames"
                  @md-changed="searchForPerson"
                  @md-opened="searchForPerson"
                  @md-selected="selectCustomer"
                >
                  <label>{{ $tc("phrases.newOwner") }}</label>
                  <template slot="md-autocomplete-item" slot-scope="{ item }">
                    {{ item.name }}
                  </template>
                </md-autocomplete>
                <md-button
                  v-if="showOwnerEdit"
                  class="md-icon-button"
                  @click="updateOwner()"
                >
                  <md-icon class="md-primary">save</md-icon>
                </md-button>
                <md-button class="md-icon-button" @click="resetOwnerEditing()">
                  <md-icon class="md-accent">cancel</md-icon>
                </md-button>
              </div>
            </div>
          </div>
        </div>
        <div class="md-layout">
          <div class="md-layout-item">{{ $tc("phrases.totalRevenue") }}:</div>
          <div class="md-layout-item">
            <div v-if="meter.totalRevenue">
              {{ moneyFormat(meter.totalRevenue) }}
            </div>
            <div v-else>{{ $tc("phrases.noData") }}</div>
          </div>
        </div>
        <div class="md-layout">
          <div class="md-layout-item">{{ $tc("phrases.lastPayment") }}:</div>
          <div class="md-layout-item">
            {{ timeForHuman(meter.lastPaymentDate) }}
          </div>
        </div>
      </div>
    </div>
  </widget>
</template>

<script>
import Widget from "@/shared/widget"
import { currency } from "@/mixins/currency"
import { PersonService } from "@/services/PersonService"
import { timing } from "@/mixins/timing"
import { MeterDetailService } from "@/services/MeterDetailService"
import { notify } from "@/mixins/notify"
import { DeviceService } from "@/services/DeviceService"

export default {
  name: "MeterBasic",
  components: { Widget },
  mixins: [currency, timing, notify],
  props: {
    meter: {
      type: Object,
    },
  },
  data() {
    return {
      deviceService: new DeviceService(),
      meterDetailService: new MeterDetailService(),
      personService: new PersonService(),
      showOwnerEdit: false,
      customerSearchTerm: "",
      searchTerm: "",
      newOwner: null,
      searchNames: [],
    }
  },
  methods: {
    async searchForPerson(term) {
      if (term !== undefined && term.length > 2) {
        try {
          this.searchNames =
            await this.meterDetailService.searchPersonForNewOwner(
              this.personService,
              term,
            )
        } catch (e) {
          this.alertNotify("error", e.message)
        }
      } else {
        this.hideSearch = true
      }
    },
    async updateOwner() {
      if (this.newOwner === null) {
        this.$swal({
          type: "error",
          title: this.$tc("phrases.meterDetailNotify", 3),
          text: this.$tc("phrases.meterDetailNotify2", 0),
        })
        return
      }
      this.$swal({
        type: "success",
        title: this.$tc("phrases.meterDetailNotify2", 1),
        text: this.$tc("phrases.meterDetailNotify2", 3, {
          newName: this.newOwner.name,
          name: this.meter.owner.name + " " + this.meter.owner.surname,
        }),
        showCancelButton: true,
        confirmButtonText: this.$tc("words.confirm"),
        cancelButtonText: this.$tc("words.cancel"),
      }).then(async (result) => {
        if (result.value) {
          try {
            const device = await this.deviceService.update(
              this.meter.deviceId,
              {
                id: this.meter.deviceId,
                personId: this.newOwner.id,
                deviceId: this.meter.id,
                deviceType: "meter",
                deviceSerial: this.meter.serialNumber,
              },
            )
            this.meter.owner = device.person
            console.log(this.meter.owner)
            this.alertNotify("success", "Updated Successfully.")
            this.resetOwnerEditing()
          } catch (e) {
            this.$swal({
              type: "error",
              title: this.$tc("phrases.meterDetailNotify"),
              text: this.$tc("phrases.meterDetailNotify", 2),
            })
          }
        }
      })
    },
    setOwner(owner) {
      this.newOwner = owner
    },
    resetOwnerEditing() {
      this.searchTerm = ""
      this.hideSearch = true
      this.searchNames = []
      this.showOwnerEdit = false
    },
    selectCustomer(c) {
      this.customerSearchTerm = c.name
      this.newOwner = c
    },
  },
}
</script>
