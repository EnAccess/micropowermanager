import CredentialRepository from './CredentialRepository'
import CertRepository from './CertRepository'

const repositories = {
    credential: CredentialRepository,
    cert: CertRepository,
}
export default {
    get: (name) => repositories[name],
}
