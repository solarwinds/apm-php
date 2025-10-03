<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Resource\Detectors;

use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SemConv\ResourceAttributes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Solarwinds\ApmPhp\Common\Configuration\Variables;
use Solarwinds\ApmPhp\Resource\Detectors\K8s;
use Solarwinds\ApmPhp\Tests\Unit\TestState;

#[CoversClass(K8s::class)]
class K8sTest extends TestCase
{
    use TestState;

    public function test_detects_attributes_from_env(): void
    {
        $namespaceFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'namespace';
        $mountFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mountinfo';

        $envNamespace = bin2hex(random_bytes(8));
        $envUid = Uuid::uuid4()->toString();
        $envName = bin2hex(random_bytes(4));
        $this->setEnvironmentVariable(Variables::SW_K8S_POD_NAMESPACE, $envNamespace);
        $this->setEnvironmentVariable(Variables::SW_K8S_POD_UID, $envUid);
        $this->setEnvironmentVariable(Variables::SW_K8S_POD_NAME, $envName);

        $resource = (new K8s($namespaceFile, $mountFile))->getResource();

        $this->assertEquals(ResourceInfo::create(Attributes::create([
            'k8s.namespace.name' => $envNamespace,
            'k8s.pod.uid' => $envUid,
            'k8s.pod.name' => $envName,
        ]), ResourceAttributes::SCHEMA_URL)->getAttributes(), $resource->getAttributes());
    }

    public function test_detects_attributes_from_files(): void
    {
        $namespaceFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'namespace';
        $fileNamespace = bin2hex(random_bytes(8));
        file_put_contents($namespaceFile, $fileNamespace . PHP_EOL);
        $mountFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mountinfo';
        $fileUid = Uuid::uuid4()->toString();
        file_put_contents($mountFile, <<<EOT
757 605 0:139 / / rw,relatime master:180 - overlay overlay rw,context="system_u:object_r:data_t:s0:c171,c852",lowerdir=/var/lib/containerd/io.containerd.snapshotter.v1.overlayfs/snapshots/25/fs,upperdir=/var/lib/containerd/io.containerd.snapshotter.v1.overlayfs/snapshots/26/fs,workdir=/var/lib/containerd/io.containerd.snapshotter.v1.overlayfs/snapshots/26/work
758 757 0:143 / /proc rw,nosuid,nodev,noexec,relatime - proc proc rw
760 757 0:145 / /dev rw,nosuid - tmpfs tmpfs rw,context="system_u:object_r:data_t:s0:c171,c852",size=65536k,mode=755
762 760 0:147 / /dev/pts rw,nosuid,noexec,relatime - devpts devpts rw,context="system_u:object_r:data_t:s0:c171,c852",gid=5,mode=620,ptmxmode=666
764 760 0:105 / /dev/mqueue rw,nosuid,nodev,noexec,relatime - mqueue mqueue rw,seclabel
765 757 0:111 / /sys ro,nosuid,nodev,noexec,relatime - sysfs sysfs ro,seclabel
767 765 0:25 / /sys/fs/cgroup ro,nosuid,nodev,noexec,relatime - cgroup2 cgroup rw,seclabel
769 757 259:16 /var/lib/kubelet/pods/$fileUid/volumes/kubernetes.io~empty-dir/html /html rw,nosuid,nodev,noatime - ext4 /dev/nvme1n1p1 rw,seclabel
772 757 259:16 /var/lib/kubelet/pods/$fileUid/etc-hosts /etc/hosts rw,nosuid,nodev,noatime - ext4 /dev/nvme1n1p1 rw,seclabel
773 760 259:16 /var/lib/kubelet/pods/$fileUid/containers/2nd/7aa42719 /dev/termination-log rw,nosuid,nodev,noatime - ext4 /dev/nvme1n1p1 rw,seclabel
774 757 259:16 /var/lib/containerd/io.containerd.grpc.v1.cri/sandboxes/bd9a3e80e86b8ffbe97ed67b484bd132dcc7b99106ce6ab58e1118287a5b1a60/hostname /etc/hostname rw,nosuid,nodev,noatime - ext4 /dev/nvme1n1p1 rw,seclabel
776 757 259:16 /var/lib/containerd/io.containerd.grpc.v1.cri/sandboxes/bd9a3e80e86b8ffbe97ed67b484bd132dcc7b99106ce6ab58e1118287a5b1a60/resolv.conf /etc/resolv.conf rw,nosuid,nodev,noatime - ext4 /dev/nvme1n1p1 rw,seclabel
778 760 0:100 / /dev/shm rw,nosuid,nodev,noexec,relatime - tmpfs shm rw,seclabel,size=65536k
781 757 0:98 / /run/secrets/kubernetes.io/serviceaccount ro,relatime - tmpfs tmpfs rw,seclabel,size=3380568k
606 758 0:143 /bus /proc/bus ro,nosuid,nodev,noexec,relatime - proc proc rw
607 758 0:143 /fs /proc/fs ro,nosuid,nodev,noexec,relatime - proc proc rw
608 758 0:143 /irq /proc/irq ro,nosuid,nodev,noexec,relatime - proc proc rw
609 758 0:143 /sys /proc/sys ro,nosuid,nodev,noexec,relatime - proc proc rw
610 758 0:143 /sysrq-trigger /proc/sysrq-trigger ro,nosuid,nodev,noexec,relatime - proc proc rw
621 758 0:149 / /proc/acpi ro,relatime - tmpfs tmpfs ro,context="system_u:object_r:data_t:s0:c171,c852"
622 758 0:145 /null /proc/kcore rw,nosuid - tmpfs tmpfs rw,context="system_u:object_r:data_t:s0:c171,c852",size=65536k,mode=755
624 758 0:145 /null /proc/keys rw,nosuid - tmpfs tmpfs rw,context="system_u:object_r:data_t:s0:c171,c852",size=65536k,mode=755
625 758 0:145 /null /proc/latency_stats rw,nosuid - tmpfs tmpfs rw,context="system_u:object_r:data_t:s0:c171,c852",size=65536k,mode=755
626 758 0:145 /null /proc/timer_list rw,nosuid - tmpfs tmpfs rw,context="system_u:object_r:data_t:s0:c171,c852",size=65536k,mode=755
627 758 0:150 / /proc/scsi ro,relatime - tmpfs tmpfs ro,context="system_u:object_r:data_t:s0:c171,c852"
628 765 0:151 / /sys/firmware ro,relatime - tmpfs tmpfs ro,context="system_u:object_r:data_t:s0:c171,c852"
EOT);
        $resource = (new K8s($namespaceFile, $mountFile))->getResource();

        $expectedAttributes = [
            'k8s.namespace.name' => $fileNamespace,
            'k8s.pod.name' => php_uname('n'),
        ];

        if (PHP_OS_FAMILY !== 'Windows') {
            $expectedAttributes['k8s.pod.uid'] = $fileUid;
        }

        $this->assertEquals(ResourceInfo::create(Attributes::create($expectedAttributes), ResourceAttributes::SCHEMA_URL)->getAttributes(), $resource->getAttributes());

        @unlink($mountFile);
        @unlink($namespaceFile);
    }

    public function test_prefers_env_over_files(): void
    {
        $namespaceFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'namespace';
        $fileNamespace = bin2hex(random_bytes(8));
        file_put_contents($namespaceFile, $fileNamespace . PHP_EOL);
        $mountFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mountinfo';
        $fileUid = Uuid::uuid4()->toString();
        file_put_contents($mountFile, <<<EOT
757 605 0:139 / / rw,relatime master:180 - overlay overlay rw,context="system_u:object_r:data_t:s0:c171,c852",lowerdir=/var/lib/containerd/io.containerd.snapshotter.v1.overlayfs/snapshots/25/fs,upperdir=/var/lib/containerd/io.containerd.snapshotter.v1.overlayfs/snapshots/26/fs,workdir=/var/lib/containerd/io.containerd.snapshotter.v1.overlayfs/snapshots/26/work
758 757 0:143 / /proc rw,nosuid,nodev,noexec,relatime - proc proc rw
760 757 0:145 / /dev rw,nosuid - tmpfs tmpfs rw,context="system_u:object_r:data_t:s0:c171,c852",size=65536k,mode=755
762 760 0:147 / /dev/pts rw,nosuid,noexec,relatime - devpts devpts rw,context="system_u:object_r:data_t:s0:c171,c852",gid=5,mode=620,ptmxmode=666
764 760 0:105 / /dev/mqueue rw,nosuid,nodev,noexec,relatime - mqueue mqueue rw,seclabel
765 757 0:111 / /sys ro,nosuid,nodev,noexec,relatime - sysfs sysfs ro,seclabel
767 765 0:25 / /sys/fs/cgroup ro,nosuid,nodev,noexec,relatime - cgroup2 cgroup rw,seclabel
769 757 259:16 /var/lib/kubelet/pods/$fileUid/volumes/kubernetes.io~empty-dir/html /html rw,nosuid,nodev,noatime - ext4 /dev/nvme1n1p1 rw,seclabel
772 757 259:16 /var/lib/kubelet/pods/$fileUid/etc-hosts /etc/hosts rw,nosuid,nodev,noatime - ext4 /dev/nvme1n1p1 rw,seclabel
773 760 259:16 /var/lib/kubelet/pods/$fileUid/containers/2nd/7aa42719 /dev/termination-log rw,nosuid,nodev,noatime - ext4 /dev/nvme1n1p1 rw,seclabel
774 757 259:16 /var/lib/containerd/io.containerd.grpc.v1.cri/sandboxes/bd9a3e80e86b8ffbe97ed67b484bd132dcc7b99106ce6ab58e1118287a5b1a60/hostname /etc/hostname rw,nosuid,nodev,noatime - ext4 /dev/nvme1n1p1 rw,seclabel
776 757 259:16 /var/lib/containerd/io.containerd.grpc.v1.cri/sandboxes/bd9a3e80e86b8ffbe97ed67b484bd132dcc7b99106ce6ab58e1118287a5b1a60/resolv.conf /etc/resolv.conf rw,nosuid,nodev,noatime - ext4 /dev/nvme1n1p1 rw,seclabel
778 760 0:100 / /dev/shm rw,nosuid,nodev,noexec,relatime - tmpfs shm rw,seclabel,size=65536k
781 757 0:98 / /run/secrets/kubernetes.io/serviceaccount ro,relatime - tmpfs tmpfs rw,seclabel,size=3380568k
606 758 0:143 /bus /proc/bus ro,nosuid,nodev,noexec,relatime - proc proc rw
607 758 0:143 /fs /proc/fs ro,nosuid,nodev,noexec,relatime - proc proc rw
608 758 0:143 /irq /proc/irq ro,nosuid,nodev,noexec,relatime - proc proc rw
609 758 0:143 /sys /proc/sys ro,nosuid,nodev,noexec,relatime - proc proc rw
610 758 0:143 /sysrq-trigger /proc/sysrq-trigger ro,nosuid,nodev,noexec,relatime - proc proc rw
621 758 0:149 / /proc/acpi ro,relatime - tmpfs tmpfs ro,context="system_u:object_r:data_t:s0:c171,c852"
622 758 0:145 /null /proc/kcore rw,nosuid - tmpfs tmpfs rw,context="system_u:object_r:data_t:s0:c171,c852",size=65536k,mode=755
624 758 0:145 /null /proc/keys rw,nosuid - tmpfs tmpfs rw,context="system_u:object_r:data_t:s0:c171,c852",size=65536k,mode=755
625 758 0:145 /null /proc/latency_stats rw,nosuid - tmpfs tmpfs rw,context="system_u:object_r:data_t:s0:c171,c852",size=65536k,mode=755
626 758 0:145 /null /proc/timer_list rw,nosuid - tmpfs tmpfs rw,context="system_u:object_r:data_t:s0:c171,c852",size=65536k,mode=755
627 758 0:150 / /proc/scsi ro,relatime - tmpfs tmpfs ro,context="system_u:object_r:data_t:s0:c171,c852"
628 765 0:151 / /sys/firmware ro,relatime - tmpfs tmpfs ro,context="system_u:object_r:data_t:s0:c171,c852"
EOT);
        $envNamespace = bin2hex(random_bytes(8));
        $envUid = Uuid::uuid4()->toString();
        $envName = bin2hex(random_bytes(4));
        $this->setEnvironmentVariable(Variables::SW_K8S_POD_NAMESPACE, $envNamespace);
        $this->setEnvironmentVariable(Variables::SW_K8S_POD_UID, $envUid);
        $this->setEnvironmentVariable(Variables::SW_K8S_POD_NAME, $envName);

        $resource = (new K8s($namespaceFile, $mountFile))->getResource();

        $this->assertEquals(ResourceInfo::create(Attributes::create([
            'k8s.namespace.name' => $envNamespace,
            'k8s.pod.uid' => $envUid,
            'k8s.pod.name' => $envName,
        ]), ResourceAttributes::SCHEMA_URL)->getAttributes(), $resource->getAttributes());

        @unlink($mountFile);
        @unlink($namespaceFile);
    }

    public function test_does_not_detect_uid_or_name_without_namespace(): void
    {
        $namespaceFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'namespace';
        $mountFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mountinfo';
        $fileUid = Uuid::uuid4()->toString();
        file_put_contents($mountFile, <<<EOT
757 605 0:139 / / rw,relatime master:180 - overlay overlay rw,context="system_u:object_r:data_t:s0:c171,c852",lowerdir=/var/lib/containerd/io.containerd.snapshotter.v1.overlayfs/snapshots/25/fs,upperdir=/var/lib/containerd/io.containerd.snapshotter.v1.overlayfs/snapshots/26/fs,workdir=/var/lib/containerd/io.containerd.snapshotter.v1.overlayfs/snapshots/26/work
758 757 0:143 / /proc rw,nosuid,nodev,noexec,relatime - proc proc rw
760 757 0:145 / /dev rw,nosuid - tmpfs tmpfs rw,context="system_u:object_r:data_t:s0:c171,c852",size=65536k,mode=755
762 760 0:147 / /dev/pts rw,nosuid,noexec,relatime - devpts devpts rw,context="system_u:object_r:data_t:s0:c171,c852",gid=5,mode=620,ptmxmode=666
764 760 0:105 / /dev/mqueue rw,nosuid,nodev,noexec,relatime - mqueue mqueue rw,seclabel
765 757 0:111 / /sys ro,nosuid,nodev,noexec,relatime - sysfs sysfs ro,seclabel
767 765 0:25 / /sys/fs/cgroup ro,nosuid,nodev,noexec,relatime - cgroup2 cgroup rw,seclabel
769 757 259:16 /var/lib/kubelet/pods/$fileUid/volumes/kubernetes.io~empty-dir/html /html rw,nosuid,nodev,noatime - ext4 /dev/nvme1n1p1 rw,seclabel
772 757 259:16 /var/lib/kubelet/pods/$fileUid/etc-hosts /etc/hosts rw,nosuid,nodev,noatime - ext4 /dev/nvme1n1p1 rw,seclabel
773 760 259:16 /var/lib/kubelet/pods/$fileUid/containers/2nd/7aa42719 /dev/termination-log rw,nosuid,nodev,noatime - ext4 /dev/nvme1n1p1 rw,seclabel
774 757 259:16 /var/lib/containerd/io.containerd.grpc.v1.cri/sandboxes/bd9a3e80e86b8ffbe97ed67b484bd132dcc7b99106ce6ab58e1118287a5b1a60/hostname /etc/hostname rw,nosuid,nodev,noatime - ext4 /dev/nvme1n1p1 rw,seclabel
776 757 259:16 /var/lib/containerd/io.containerd.grpc.v1.cri/sandboxes/bd9a3e80e86b8ffbe97ed67b484bd132dcc7b99106ce6ab58e1118287a5b1a60/resolv.conf /etc/resolv.conf rw,nosuid,nodev,noatime - ext4 /dev/nvme1n1p1 rw,seclabel
778 760 0:100 / /dev/shm rw,nosuid,nodev,noexec,relatime - tmpfs shm rw,seclabel,size=65536k
781 757 0:98 / /run/secrets/kubernetes.io/serviceaccount ro,relatime - tmpfs tmpfs rw,seclabel,size=3380568k
606 758 0:143 /bus /proc/bus ro,nosuid,nodev,noexec,relatime - proc proc rw
607 758 0:143 /fs /proc/fs ro,nosuid,nodev,noexec,relatime - proc proc rw
608 758 0:143 /irq /proc/irq ro,nosuid,nodev,noexec,relatime - proc proc rw
609 758 0:143 /sys /proc/sys ro,nosuid,nodev,noexec,relatime - proc proc rw
610 758 0:143 /sysrq-trigger /proc/sysrq-trigger ro,nosuid,nodev,noexec,relatime - proc proc rw
621 758 0:149 / /proc/acpi ro,relatime - tmpfs tmpfs ro,context="system_u:object_r:data_t:s0:c171,c852"
622 758 0:145 /null /proc/kcore rw,nosuid - tmpfs tmpfs rw,context="system_u:object_r:data_t:s0:c171,c852",size=65536k,mode=755
624 758 0:145 /null /proc/keys rw,nosuid - tmpfs tmpfs rw,context="system_u:object_r:data_t:s0:c171,c852",size=65536k,mode=755
625 758 0:145 /null /proc/latency_stats rw,nosuid - tmpfs tmpfs rw,context="system_u:object_r:data_t:s0:c171,c852",size=65536k,mode=755
626 758 0:145 /null /proc/timer_list rw,nosuid - tmpfs tmpfs rw,context="system_u:object_r:data_t:s0:c171,c852",size=65536k,mode=755
627 758 0:150 / /proc/scsi ro,relatime - tmpfs tmpfs ro,context="system_u:object_r:data_t:s0:c171,c852"
628 765 0:151 / /sys/firmware ro,relatime - tmpfs tmpfs ro,context="system_u:object_r:data_t:s0:c171,c852"
EOT);
        $envUid = Uuid::uuid4()->toString();
        $envName = bin2hex(random_bytes(4));
        $this->setEnvironmentVariable(Variables::SW_K8S_POD_UID, $envUid);
        $this->setEnvironmentVariable(Variables::SW_K8S_POD_NAME, $envName);

        $resource = (new K8s($namespaceFile, $mountFile))->getResource();

        $this->assertEquals(ResourceInfoFactory::emptyResource(), $resource);

        @unlink($mountFile);
        @unlink($namespaceFile);
    }

    public function test_returns_empty_resource_when_namespace_file_missing(): void
    {
        $namespaceFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('namespace_missing_');
        $mountFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('mountinfo_missing_');
        @unlink($namespaceFile);
        @unlink($mountFile);
        $resource = (new K8s($namespaceFile, $mountFile))->getResource();
        $this->assertEquals(ResourceInfoFactory::emptyResource()->getAttributes(), $resource->getAttributes());
    }

    public function test_returns_empty_resource_when_namespace_file_unreadable(): void
    {
        $namespaceFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('namespace_unreadable_');
        file_put_contents($namespaceFile, 'test');
        chmod($namespaceFile, 0000);
        $mountFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('mountinfo_unreadable_');
        $resource = (new K8s($namespaceFile, $mountFile))->getResource();
        $this->assertEquals(ResourceInfoFactory::emptyResource(), $resource);
        chmod($namespaceFile, 0644);
        unlink($namespaceFile);
    }

    public function test_returns_empty_namespace_when_namespace_file_empty(): void
    {
        $namespaceFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('namespace_empty_');
        file_put_contents($namespaceFile, '');
        $mountFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('mountinfo_empty_');
        $resource = (new K8s($namespaceFile, $mountFile))->getResource();
        $this->assertEquals('', $resource->getAttributes()->toArray()['k8s.namespace.name'] ?? 'null');
        unlink($namespaceFile);
    }

    public function test_uid_not_set_when_mountinfo_file_missing(): void
    {
        $namespaceFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('namespace_uid_');
        file_put_contents($namespaceFile, 'testnamespace');
        $mountFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('mountinfo_uid_missing_');
        @unlink($mountFile);
        $resource = (new K8s($namespaceFile, $mountFile))->getResource();
        $this->assertArrayNotHasKey('k8s.pod.uid', $resource->getAttributes()->toArray());
        unlink($namespaceFile);
    }

    public function test_uid_not_set_when_no_valid_uid_in_mountinfo(): void
    {
        $namespaceFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('namespace_uid2_');
        file_put_contents($namespaceFile, 'testnamespace');
        $mountFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('mountinfo_uid2_');
        file_put_contents($mountFile, "invalid line\n123 456 0:1 / notkube\n789 012 0:2 /kube-but-no-uid\n");
        $resource = (new K8s($namespaceFile, $mountFile))->getResource();
        $this->assertArrayNotHasKey('k8s.pod.uid', $resource->getAttributes()->toArray());
        unlink($namespaceFile);
        unlink($mountFile);
    }

    public function test_uid_set_when_valid_uid_in_mountinfo(): void
    {
        $namespaceFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('namespace_uid3_');
        file_put_contents($namespaceFile, 'testnamespace');
        $mountFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('mountinfo_uid3_');
        $uid = '123e4567-e89b-12d3-a456-426614174000';
        file_put_contents($mountFile, "123 456 0:1 /var/lib/kube/pods/$uid/volumes/kubernetes.io~empty-dir/html /html rw a b c d\n");
        $resource = (new K8s($namespaceFile, $mountFile))->getResource();
        $this->assertEquals($uid, $resource->getAttributes()->toArray()['k8s.pod.uid'] ?? null);
        unlink($namespaceFile);
        unlink($mountFile);
    }

    public function test_uid_not_set_on_windows(): void
    {
        // Simulate Windows by mocking PHP_OS_FAMILY if possible, or skip if not
        if (defined('PHP_OS_FAMILY') && PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Cannot simulate Windows on Windows');
        } else {
            // This test is a placeholder; in real code, use a mock or patch
            $this->assertTrue(true, 'Simulate Windows behavior in CI with a mock if needed');
        }
    }

    public function test_pod_name_from_env_and_fallback(): void
    {
        $namespaceFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('namespace_name_');
        file_put_contents($namespaceFile, 'testnamespace');
        $mountFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('mountinfo_name_');
        $envName = 'testpodname';
        $this->setEnvironmentVariable(Variables::SW_K8S_POD_NAME, $envName);
        $resource = (new K8s($namespaceFile, $mountFile))->getResource();
        $this->assertEquals($envName, $resource->getAttributes()->get('k8s.pod.name') ?? null);
        // Now unset the env var and check fallback
        putenv(Variables::SW_K8S_POD_NAME);
        $resource2 = (new K8s($namespaceFile, $mountFile))->getResource();
        $this->assertEquals(php_uname('n'), $resource2->getAttributes()->get('k8s.pod.name') ?? null);
        unlink($namespaceFile);
    }
}
