<?php
namespace TYPO3\CMS\Composer\Installer\CoreInstaller;

/**
 * Enter descriptions here
 */
class GetTypo3OrgService {

	/**
	 * @var string
	 */
	protected $file;

	/**
	 * @var array
	 */
	protected $data = array();

	/**
	 * @param \Composer\IO\IOInterface $io
	 * @param string $jsonUrl
	 */
	public function __construct(\Composer\IO\IOInterface $io, $jsonUrl = 'https://get.typo3.org/json') {
		$this->file = new \Composer\Json\JsonFile($jsonUrl, new \Composer\Util\RemoteFilesystem($io));
	}

	/**
	 *
	 */
	protected function initializeData() {
		if (empty($this->data)) {
			$this->data = $this->file->read();
		}
	}

	/**
	 * @param \Composer\Package\Package $package
	 */
	public function addDistToPackage(\Composer\Package\Package $package) {
		$this->initializeData();
		$versionDigits = explode('.', $package->getPrettyVersion());
		if (count($versionDigits) === 3) {
			$branchVersion = $versionDigits[0] . '.' . $versionDigits[1];
			$patchlevelVersion = $versionDigits[0] . '.' . $versionDigits[1] . '.' . $versionDigits[2];
			if (isset($this->data[$branchVersion]) && isset($this->data[$branchVersion]['releases'][$patchlevelVersion])) {
				$releaseData = $this->data[$branchVersion]['releases'][$patchlevelVersion];
				if (isset($releaseData['checksums']['tar']['sha1']) && isset($releaseData['url']['tar'])) {
					$package->setDistType('tar');
					$package->setDistReference($patchlevelVersion);
					$package->setDistUrl($releaseData['url']['tar']);
					$package->setDistSha1Checksum($releaseData['checksums']['tar']['sha1']);
				}
			}
		}
	}

}