import CredentialRepository from './CredentialRepository'
import PaymentRepository from './PaymentRepository'

const repositories = {
    credential: CredentialRepository,
    payment: PaymentRepository,
}
export default {
    get: (name) => repositories[name],
}
