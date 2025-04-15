<template>
  <widget
    :title="$tc('words.detail', 2)"
    :button="true"
    :button-text="$tc('phrases.deleteCustomer', 0)"
    @widgetAction="confirmDelete"
    button-icon="delete"
    :show-spinner="false"
  >
    <md-card>
      <md-card-content>
        <div class="md-layout md-gutter" v-if="!editPerson">
          <div
            class="md-layout-item md-large-size-15 md-medium-size-20 md-small-size-25"
          >
            <md-icon class="md-size-3x">account_circle</md-icon>
          </div>
          <div class="md-layout-item md-size-65">
            <h3>
              {{ this.personService.person.title }}
              {{ this.personService.person.name }}
              {{ this.personService.person.surname }}
            </h3>
          </div>
          <div
            class="md-layout-item md-large-size-20 md-medium-size-15 md-small-size-10"
          >
            <md-button
              @click="editPerson = true"
              class="md-icon-button"
              style="float: right"
            >
              <md-icon>create</md-icon>
            </md-button>
          </div>
          <div class="md-layout-item md-size-100">&nbsp;</div>
          <div class="md-layout-item md-size-15">
            <md-icon>wc</md-icon>
            {{ $tc("words.gender") }}:
          </div>
          <div class="md-layout-item md-size-15">
            {{ this.personService.person.gender }}
          </div>

          <div class="md-layout-item md-size-20">
            <md-icon>school</md-icon>
            &nbsp;{{ $tc("words.education") }}:
          </div>
          <div class="md-layout-item md-size-15">
            {{ this.personService.person.education }}
          </div>

          <div class="md-layout-item md-size-15">
            <md-icon>cake</md-icon>
            &nbsp;{{ $tc("words.birthday") }}:
          </div>
          <div class="md-layout-item md-size-15">
            {{ this.personService.person.birthDate }}
          </div>
        </div>

        <div class="md-layout md-gutter" v-else>
          <div class="md-layout-item md-size-100">
            <form class="md-layout" @submit.prevent="updatePerson">
              <md-field>
                <label for="title">
                  {{ $tc("words.title") }}
                </label>
                <md-input
                  type="text"
                  name="person-title"
                  id="person-title"
                  v-model="personService.person.title"
                />
              </md-field>
              <md-field
                :class="{
                  'md-invalid': errors.has($tc('words.title')),
                }"
              >
                <label for="name">
                  {{ $tc("words.name") }}
                </label>
                <md-input
                  type="text"
                  name="name"
                  id="name"
                  v-validate="'required'"
                  v-model="personService.person.name"
                />
                <span class="md-error">
                  {{ errors.first($tc($tc("words.name"))) }}
                </span>
              </md-field>
              <md-field
                :class="{
                  'md-invalid': errors.has($tc('words.surname')),
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
                  v-validate="'required'"
                />
                <span class="md-error">
                  {{ errors.first($tc($tc("words.surname"))) }}
                </span>
              </md-field>
              <md-datepicker
                name="birthDate"
                md-immediately
                v-model="personService.person.birthDate"
                :md-close-on-blur="false"
              >
                <label for="birth-date">{{ $tc("words.birthday") }} :</label>
              </md-datepicker>
              <md-field>
                <label for="gender">{{ $tc("words.gender") }} :</label>
                <md-select
                  name="gender"
                  id="gender"
                  v-model="personService.person.gender"
                >
                  <md-option
                    disabled
                    v-if="personService.person.gender == null"
                  >
                    -- {{ $tc("words.select") }} --
                  </md-option>
                  <md-option value="male">
                    {{ $tc("words.male") }}
                  </md-option>
                  <md-option value="female">
                    {{ $tc("words.female") }}
                  </md-option>
                </md-select>
              </md-field>
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
              <div class="md-layout-item md-size-100">
                <md-button
                  type="submit"
                  class="md-raised md-primary"
                  style="float: right !important"
                >
                  {{ $tc("words.save") }}
                </md-button>
                <md-button
                  type="button"
                  @click="editPerson = false"
                  class="md-raised"
                  style="float: right !important"
                >
                  {{ $tc("words.cancel") }}
                </md-button>
              </div>
            </form>
          </div>
        </div>
      </md-card-content>
    </md-card>
  </widget>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { PersonService } from "@/services/PersonService"

export default {
  name: "ClientPersonalData",
  components: {
    Widget,
  },
  props: {
    person: {
      required: true,
    },
  },
  data() {
    return {
      personService: new PersonService(),
      editPerson: false,
    }
  },
  mounted() {
    this.personService.person = this.person
  },
  methods: {
    async updatePerson() {
      const validator = await this.$validator.validateAll()
      if (!validator) return
      const personParams = {
        id: this.personService.person.id,
        name: this.personService.person.name,
        surname: this.personService.person.surname,
        title: this.personService.person.title,
        education: this.personService.person.education,
        birthDate: this.personService.person.birthDate,
        sex: this.personService.person.gender,
      }
      await this.personService.updatePerson(personParams)
      this.editPerson = false
    },
    confirmDelete() {
      this.$swal({
        type: "question",
        title: this.$tc("phrases.deleteCustomer", 0),
        width: "35%",
        confirmButtonText: this.$tc("words.confirm"),
        showCancelButton: true,
        cancelButtonText: this.$tc("words.cancel"),
        focusCancel: true,
        html:
          '<div style="text-align: left; padding-left: 5rem" class="checkbox">' +
          "  <label>" +
          '    <input type="checkbox" name="confirmation" id="confirmation" >' +
          this.$tc("phrases.deleteCustomerNotify", 0, {
            name: this.personService.person.name,
            surname: this.personService.person.surname,
          }) +
          "  </label>" +
          "</div>",
      }).then((result) => {
        let answer = document.getElementById("confirmation").checked
        if ("value" in result) {
          //delete customer
          if (answer) {
            this.deletePerson()
          } else {
            //not confirmed
          }
        }
      })
    },
    deletePerson() {
      this.personService
        .deletePerson(this.personService.person.id)
        .then((response) => {
          if (response.status === 200) {
            this.showConfirmation()
          }
        })
    },
    showConfirmation() {
      const Toast = this.$swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        onOpen: (toast) => {
          toast.addEventListener("mouseenter", this.$swal.stopTimer)
          toast.addEventListener("mouseleave", this.$swal.resumeTimer)
        },
      })

      Toast.fire({
        type: "success",
        title: this.$tc("phrases.deleteCustomer", 1),
      }).then((x) => {
        console.log(x)
        window.history.back()
      })
    },
  },
}
</script>

<style></style>
