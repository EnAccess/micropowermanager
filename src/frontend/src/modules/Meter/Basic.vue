<template>
  <widget :title="$tc('words.basic')" color="green">
    <div class="meter-overview-card">
      <div
        class="meter-overview-detail"
        v-if="meter !== null && meter.loaded === true"
      >
        <md-list class="md-double-line">
          <md-list-item>
            <div class="md-list-item-text">
              <span>{{ $tc("words.register", 2) }}</span>
              <span>{{ timeForTimeZone(meter.registered) }}</span>
            </div>
          </md-list-item>
          <md-divider></md-divider>

          <md-list-item>
            <div class="">
              <span class="">{{ $tc("words.owner") }}</span>
              <div v-if="!showOwnerEdit" class="column">
                <router-link :to="`/people/${meter.owner.id}`">
                  {{ meter.owner.name }}
                  {{ meter.owner.surname }}
                </router-link>
                <span style="cursor: pointer" @click="showOwnerEdit = true">
                  <md-icon>edit</md-icon>
                </span>
              </div>
              <div v-if="showOwnerEdit">
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
                <div class="check">
                  <md-button class="md-icon-button" @click="updateOwner()">
                    <md-icon class="md-primary">save</md-icon>
                  </md-button>
                  <md-button
                    class="md-icon-button"
                    @click="resetOwnerEditing()"
                  >
                    <md-icon class="md-accent">cancel</md-icon>
                  </md-button>
                </div>
              </div>
            </div>
          </md-list-item>
          <md-divider></md-divider>

          <md-list-item>
            <div class="md-list-item-text">
              <span>{{ $tc("phrases.totalRevenue") }}</span>
              <span>
                <div v-if="meter.totalRevenue">
                  {{ moneyFormat(meter.totalRevenue) }}
                </div>
                <div v-else>{{ $tc("phrases.noData") }}</div>
              </span>
            </div>
          </md-list-item>
          <md-divider></md-divider>

          <md-list-item>
            <div class="md-list-item-text">
              <span>{{ $tc("phrases.lastPayment") }}</span>
              <span>{{ timeForHuman(meter.lastPaymentDate) }}</span>
            </div>
          </md-list-item>
        </md-list>
      </div>
    </div>
  </widget>
</template>

<script>
import Widget from "@/shared/Widget.vue"
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
  mounted() {
    this.$emit("widget-loaded", "meter-basic")
  },
}
</script>

<style scoped>
.md-list-item-text {
  display: flex;
  flex-direction: column;
  align-items: center;
}
.column {
  display: flex;
  align-items: center;
  gap: 4px;
}
.check {
  margin-right: 10px;
}
</style>
