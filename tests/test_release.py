import importlib.util
import json
from pathlib import Path
import tempfile
import unittest
import zipfile


ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location("build_release", ROOT / "tools/build_release.py")
release = importlib.util.module_from_spec(spec)
spec.loader.exec_module(release)


class ReleaseTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.output = Path(self.temp.name) / "dist"
        self.source = ROOT / "Modules/CustomerSystems"
        self.version = json.loads((self.source / "module.json").read_text(encoding="utf-8"))["version"]

    def test_freescout_archive_layout_and_updater_metadata(self):
        release.build(self.source, self.output, self.version, "test-owner/test-repo")
        with zipfile.ZipFile(self.output / "CustomerSystems.zip") as archive:
            names = archive.namelist()
            self.assertTrue(all(name.startswith("CustomerSystems/") for name in names))
            self.assertIn("CustomerSystems/Providers/CustomerSystemsServiceProvider.php", names)
            self.assertIn("CustomerSystems/Public/js/sidebar.js", names)
            self.assertFalse(any("Shopware5/" in name or ".github/" in name or "tests/" in name for name in names))
            manifest = json.loads(archive.read("CustomerSystems/module.json"))
            self.assertEqual(manifest["version"], self.version)
            self.assertEqual(manifest["latestVersionUrl"], "https://github.com/test-owner/test-repo/releases/latest/download/version.txt")
            self.assertEqual(manifest["latestVersionZipUrl"], "https://github.com/test-owner/test-repo/releases/latest/download/CustomerSystems.zip")
            self.assertIsNone(archive.testzip())
        self.assertEqual((self.output / "version.txt").read_text(encoding="ascii"), self.version + "\n")

    def test_tag_mismatch_produces_no_assets(self):
        with self.assertRaisesRegex(ValueError, "does not match"):
            release.build(self.source, self.output, "999.0.0", "test-owner/test-repo")
        self.assertFalse(self.output.exists())

    def test_rejects_invalid_tags_and_repository_values(self):
        for tag in ["v" + self.version, "1.0", "1.0.0;echo bad", "1.0.0-beta"]:
            with self.subTest(tag=tag), self.assertRaises(ValueError):
                release.build(self.source, self.output, tag, "test-owner/test-repo")
        for repository in ["", "repo", "https://github.com/owner/repo", "owner/repo?query=1"]:
            with self.subTest(repository=repository), self.assertRaises(ValueError):
                release.build(self.source, self.output, self.version, repository)

    def test_local_secret_file_is_not_packaged(self):
        source = Path(self.temp.name) / "module"
        source.mkdir()
        (source / "module.json").write_text(json.dumps({"version": self.version, "alias": "customersystems"}), encoding="utf-8")
        (source / ".env").write_text("SECRET=test", encoding="utf-8")
        with self.assertRaisesRegex(ValueError, "Hidden/local"):
            release.build(source, self.output, self.version, "test-owner/test-repo")
        self.assertFalse(self.output.exists())


if __name__ == "__main__":
    unittest.main()
